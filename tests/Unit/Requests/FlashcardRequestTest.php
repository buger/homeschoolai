<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\FlashcardRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FlashcardRequestTest extends TestCase
{
    use RefreshDatabase;

    private function makeRequest(array $data): FlashcardRequest
    {
        $request = new FlashcardRequest;
        $request->merge($data);
        $request->setMethod('POST');

        // Call prepareForValidation to trigger data transformations
        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        return $request;
    }

    #[Test]
    public function it_validates_basic_card_type()
    {
        $request = $this->makeRequest([
            'card_type' => 'basic',
            'question' => 'Test question?',
            'answer' => 'Test answer',
            'difficulty_level' => 'medium',
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_requires_question_for_basic_cards()
    {
        $request = $this->makeRequest([
            'card_type' => 'basic',
            'answer' => 'Test answer',
            'difficulty_level' => 'medium',
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('question'));
    }

    #[Test]
    public function it_requires_answer_for_basic_cards()
    {
        $request = $this->makeRequest([
            'card_type' => 'basic',
            'question' => 'Test question?',
            'difficulty_level' => 'medium',
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('answer'));
    }

    #[Test]
    public function it_validates_multiple_choice_card_type()
    {
        $request = $this->makeRequest([
            'card_type' => 'multiple_choice',
            'question' => 'Pick the correct options',
            'answer' => 'A, C',
            'choices' => ['A', 'B', 'C', 'D'],
            'correct_choices' => [0, 2],
            'difficulty_level' => 'medium',
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_requires_minimum_choices_for_multiple_choice()
    {
        $request = $this->makeRequest([
            'card_type' => 'multiple_choice',
            'question' => 'Pick the correct option',
            'answer' => 'A',
            'choices' => ['A'], // Only one choice
            'correct_choices' => [0],
            'difficulty_level' => 'medium',
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('choices'));
    }

    #[Test]
    public function it_requires_correct_choices_for_multiple_choice()
    {
        $request = $this->makeRequest([
            'card_type' => 'multiple_choice',
            'question' => 'Pick the correct options',
            'answer' => 'A',
            'choices' => ['A', 'B', 'C'],
            // Missing correct_choices
            'difficulty_level' => 'medium',
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('correct_choices'));
    }

    #[Test]
    public function it_validates_true_false_card_type()
    {
        $request = $this->makeRequest([
            'card_type' => 'true_false',
            'question' => 'The Earth is round',
            'answer' => 'True',
            'true_false_answer' => 'true',
            'difficulty_level' => 'easy',
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_requires_true_false_answer()
    {
        $request = $this->makeRequest([
            'card_type' => 'true_false',
            'question' => 'The Earth is round',
            'answer' => 'True',
            'difficulty_level' => 'easy',
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('true_false_answer'));
    }

    #[Test]
    public function it_validates_cloze_card_type()
    {
        $request = $this->makeRequest([
            'card_type' => 'cloze',
            'cloze_text' => 'The {{capital}} of France is {{Paris}}.',
            'difficulty_level' => 'medium',
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_requires_cloze_text_for_cloze_cards()
    {
        $request = $this->makeRequest([
            'card_type' => 'cloze',
            'difficulty_level' => 'medium',
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('cloze_text'));
    }

    #[Test]
    public function it_validates_image_occlusion_card_type()
    {
        $request = $this->makeRequest([
            'card_type' => 'image_occlusion',
            'question' => 'Identify the organ',
            'answer' => 'Heart',
            'question_image_url' => 'https://example.com/anatomy.jpg',
            'occlusion_data' => [
                ['x' => 100, 'y' => 50, 'width' => 80, 'height' => 60],
            ],
            'difficulty_level' => 'hard',
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_requires_image_url_for_image_occlusion()
    {
        $request = $this->makeRequest([
            'card_type' => 'image_occlusion',
            'question' => 'Identify the organ',
            'answer' => 'Heart',
            'difficulty_level' => 'hard',
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('question_image_url'));
    }

    #[Test]
    public function it_validates_card_type_enum()
    {
        $request = $this->makeRequest([
            'card_type' => 'invalid_type',
            'question' => 'Test question',
            'answer' => 'Test answer',
            'difficulty_level' => 'medium',
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('card_type'));
    }

    #[Test]
    public function it_validates_difficulty_level_enum()
    {
        $request = $this->makeRequest([
            'card_type' => 'basic',
            'question' => 'Test question',
            'answer' => 'Test answer',
            'difficulty_level' => 'invalid_difficulty',
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('difficulty_level'));
    }

    #[Test]
    public function it_validates_choices_maximum_count()
    {
        $request = $this->makeRequest([
            'card_type' => 'multiple_choice',
            'question' => 'Pick the correct option',
            'answer' => 'A',
            'choices' => ['A', 'B', 'C', 'D', 'E', 'F', 'G'], // 7 choices, max is 6
            'correct_choices' => [0],
            'difficulty_level' => 'medium',
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('choices'));
    }

    #[Test]
    public function it_validates_image_url_format()
    {
        $request = $this->makeRequest([
            'card_type' => 'image_occlusion',
            'question' => 'Identify the organ',
            'answer' => 'Heart',
            'question_image_url' => 'not-a-url',
            'difficulty_level' => 'hard',
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('question_image_url'));
    }

    #[Test]
    public function it_prepares_true_false_data()
    {
        $request = $this->makeRequest([
            'card_type' => 'true_false',
            'question' => 'The Earth is round',
            'true_false_answer' => 'true',
            'difficulty_level' => 'easy',
        ]);

        // This should automatically set choices and correct_choices
        $this->assertEquals(['True', 'False'], $request->input('choices'));
        $this->assertEquals([0], $request->input('correct_choices'));
        $this->assertEquals('True', $request->input('answer'));
    }

    #[Test]
    public function it_prepares_true_false_false_answer()
    {
        $request = $this->makeRequest([
            'card_type' => 'true_false',
            'question' => 'The Earth is flat',
            'true_false_answer' => 'false',
            'difficulty_level' => 'easy',
        ]);

        $this->assertEquals([1], $request->input('correct_choices'));
        $this->assertEquals('False', $request->input('answer'));
    }

    #[Test]
    public function it_prepares_cloze_data()
    {
        $request = $this->makeRequest([
            'card_type' => 'cloze',
            'cloze_text' => 'The {{capital}} of France is {{Paris}}.',
            'difficulty_level' => 'medium',
        ]);

        $this->assertStringContainsString('[...]', $request->input('question'));
        $this->assertEquals('capital, Paris', $request->input('answer'));
        $this->assertEquals(['capital', 'Paris'], $request->input('cloze_answers'));
    }

    #[Test]
    public function it_prepares_tags_from_string()
    {
        $request = $this->makeRequest([
            'card_type' => 'basic',
            'question' => 'Test question',
            'answer' => 'Test answer',
            'tags' => 'math, science, basic',
            'difficulty_level' => 'medium',
        ]);

        $this->assertEquals(['math', 'science', 'basic'], $request->input('tags'));
    }

    #[Test]
    public function it_handles_empty_tags_string()
    {
        $request = $this->makeRequest([
            'card_type' => 'basic',
            'question' => 'Test question',
            'answer' => 'Test answer',
            'tags' => '',
            'difficulty_level' => 'medium',
        ]);

        $this->assertEquals([], $request->input('tags'));
    }

    #[Test]
    public function it_handles_anki_style_cloze_syntax()
    {
        $request = $this->makeRequest([
            'card_type' => 'cloze',
            'cloze_text' => 'The {{c1::mitochondria}} is the {{c2::powerhouse}} of the cell.',
            'difficulty_level' => 'medium',
        ]);

        $this->assertEquals(['mitochondria', 'powerhouse'], $request->input('cloze_answers'));
        $this->assertEquals('mitochondria, powerhouse', $request->input('answer'));
    }

    #[Test]
    public function it_provides_custom_error_messages()
    {
        $request = $this->makeRequest([
            'card_type' => 'multiple_choice',
            'question' => 'Test question',
            'answer' => 'A',
            'choices' => ['A'], // Too few choices
            'correct_choices' => [],
            'difficulty_level' => 'medium',
        ]);

        $messages = $request->messages();

        $this->assertArrayHasKey('choices.min', $messages);
        $this->assertArrayHasKey('correct_choices.min', $messages);
        $this->assertEquals('Multiple choice cards must have at least 2 choices.', $messages['choices.min']);
        $this->assertEquals('Multiple choice cards must have at least 1 correct choice.', $messages['correct_choices.min']);
    }
}
