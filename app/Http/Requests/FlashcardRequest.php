<?php

namespace App\Http\Requests;

use App\Models\Flashcard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FlashcardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $response = response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors()->toArray(),
        ], 422);

        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'card_type' => ['required', 'string', Rule::in(Flashcard::getCardTypes())],
            'difficulty_level' => ['required', 'string', Rule::in(Flashcard::getDifficultyLevels())],
            'tags' => 'nullable|array', // Converted from string in prepareForValidation
            'tags.*' => 'string|max:100',
            'is_active' => 'nullable|boolean',
            'hint' => 'nullable|string|max:65535',
            'topic_id' => 'nullable|integer|exists:topics,id',
        ];

        // Add card-type specific rules
        $cardType = $this->input('card_type', 'basic');

        switch ($cardType) {
            case Flashcard::CARD_TYPE_BASIC:
            case Flashcard::CARD_TYPE_TYPED_ANSWER:
                $rules = array_merge($rules, [
                    'question' => 'required|string|max:65535',
                    'answer' => 'required|string|max:65535',
                ]);
                break;

            case Flashcard::CARD_TYPE_MULTIPLE_CHOICE:
                $rules = array_merge($rules, [
                    'question' => 'required|string|max:65535',
                    'answer' => 'required|string|max:65535',
                    'choices' => 'required|array|min:2|max:6',
                    'choices.*' => 'required|string|max:1000',
                    'correct_choices' => 'present|array|min:1',
                    'correct_choices.*' => 'integer|min:0',
                ]);
                break;

            case Flashcard::CARD_TYPE_TRUE_FALSE:
                $rules = array_merge($rules, [
                    'question' => 'required|string|max:65535',
                    'answer' => 'required|string|max:65535',
                    'true_false_answer' => 'required|in:true,false',
                    'choices' => 'array|size:2', // Added by prepareForValidation
                    'correct_choices' => 'array|size:1', // Added by prepareForValidation
                ]);
                break;

            case Flashcard::CARD_TYPE_CLOZE:
                $rules = array_merge($rules, [
                    'cloze_text' => 'required|string|max:65535',
                    'question' => 'string|max:65535', // Added by prepareForValidation
                    'answer' => 'string|max:65535', // Added by prepareForValidation
                    'cloze_answers' => 'array', // Added by prepareForValidation
                ]);
                break;

            case Flashcard::CARD_TYPE_IMAGE_OCCLUSION:
                $rules = array_merge($rules, [
                    'question' => 'required|string|max:65535',
                    'answer' => 'required|string|max:65535',
                    'question_image_url' => 'required|string|url|max:255',
                    'answer_image_url' => 'nullable|string|url|max:255',
                    'occlusion_data' => 'required|array',
                ]);
                break;
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'choices.min' => 'Multiple choice cards must have at least 2 choices.',
            'choices.max' => 'Multiple choice cards can have at most 6 choices.',
            'correct_choices.min' => 'Multiple choice cards must have at least 1 correct choice.',
            'correct_choices.*.integer' => 'Correct choices must be valid choice indices.',
            'cloze_text.required' => 'Cloze deletion cards must have cloze text with {{}} syntax.',
            'question_image_url.required' => 'Image occlusion cards must have a question image URL.',
            'question_image_url.url' => 'Question image URL must be a valid URL.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $cardType = $this->input('card_type');

            // Custom validation for each card type
            switch ($cardType) {
                case Flashcard::CARD_TYPE_MULTIPLE_CHOICE:
                    $this->validateMultipleChoice($validator);
                    break;

                case Flashcard::CARD_TYPE_TRUE_FALSE:
                    $this->validateTrueFalse($validator);
                    break;

                case Flashcard::CARD_TYPE_CLOZE:
                    $this->validateCloze($validator);
                    break;

                case Flashcard::CARD_TYPE_IMAGE_OCCLUSION:
                    $this->validateImageOcclusion($validator);
                    break;
            }
        });
    }

    /**
     * Validate multiple choice specific rules
     */
    private function validateMultipleChoice($validator): void
    {
        $choices = $this->input('choices', []);
        $correctChoices = $this->input('correct_choices', []);

        // Ensure correct choices indices are valid
        foreach ($correctChoices as $index) {
            if (! isset($choices[$index])) {
                $validator->errors()->add('correct_choices', "Correct choice index {$index} is invalid.");
            }
        }

        // Ensure choices are unique
        if (count($choices) !== count(array_unique($choices))) {
            $validator->errors()->add('choices', 'All choices must be unique.');
        }
    }

    /**
     * Validate true/false specific rules
     */
    private function validateTrueFalse($validator): void
    {
        $trueFalseAnswer = $this->input('true_false_answer');

        if (! in_array($trueFalseAnswer, ['true', 'false'])) {
            $validator->errors()->add('true_false_answer', 'Must select either True or False.');
        }
    }

    /**
     * Validate cloze deletion specific rules
     */
    private function validateCloze($validator): void
    {
        $clozeText = $this->input('cloze_text', '');

        // Check for cloze syntax
        if (! preg_match('/\{\{[^}]+\}\}/', $clozeText)) {
            $validator->errors()->add('cloze_text', 'Cloze text must contain at least one deletion using {{}} syntax.');
        }

        // Check for nested or malformed cloze syntax
        if (preg_match('/\{\{[^}]*\{\{/', $clozeText) || preg_match('/\}\}[^{]*\}\}/', $clozeText)) {
            $validator->errors()->add('cloze_text', 'Invalid cloze syntax. Use {{word}} or {{c1::word}} format.');
        }

        // Ensure cloze deletions are not empty
        preg_match_all('/\{\{([^}]*)\}\}/', $clozeText, $matches);
        foreach ($matches[1] as $deletion) {
            $cleanedDeletion = preg_replace('/^c\d+::/', '', $deletion);
            if (empty(trim($cleanedDeletion))) {
                $validator->errors()->add('cloze_text', 'Cloze deletions cannot be empty.');
                break;
            }
        }
    }

    /**
     * Validate image occlusion specific rules
     */
    private function validateImageOcclusion($validator): void
    {
        $questionImageUrl = $this->input('question_image_url', '');

        if (! empty($questionImageUrl)) {
            // Check if URL points to an image
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            $extension = strtolower(pathinfo(parse_url($questionImageUrl, PHP_URL_PATH), PATHINFO_EXTENSION));

            if (! in_array($extension, $imageExtensions)) {
                $validator->errors()->add('question_image_url', 'URL must point to a valid image file (jpg, png, gif, etc.).');
            }
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Handle true/false conversion
        if ($this->input('card_type') === Flashcard::CARD_TYPE_TRUE_FALSE) {
            $trueFalseAnswer = $this->input('true_false_answer');
            if ($trueFalseAnswer) {
                $this->merge([
                    'choices' => ['True', 'False'],
                    'correct_choices' => [$trueFalseAnswer === 'true' ? 0 : 1],
                    'answer' => $trueFalseAnswer === 'true' ? 'True' : 'False',
                ]);
            }
        }

        // Handle cloze deletion conversion
        if ($this->input('card_type') === Flashcard::CARD_TYPE_CLOZE) {
            $clozeText = $this->input('cloze_text', '');
            if (! empty($clozeText)) {
                // Extract cloze answers
                preg_match_all('/\{\{(?:c\d+::)?([^}]+)\}\}/', $clozeText, $matches);
                $clozeAnswers = array_unique($matches[1]);

                $this->merge([
                    'question' => preg_replace('/\{\{[^}]*\}\}/', '[...]', $clozeText),
                    'answer' => implode(', ', $clozeAnswers),
                    'cloze_answers' => $clozeAnswers,
                ]);
            }
        }

        // Convert tags string to array
        if ($this->has('tags') && is_string($this->input('tags'))) {
            $tags = array_filter(array_map('trim', explode(',', $this->input('tags'))));
            $this->merge(['tags' => $tags]);
        }
    }
}
