<?php

/**
 * Ok, glad you are here
 * first we get a config instance, and set the settings
 * $config = HTMLPurifier_Config::createDefault();
 * $config->set('Core.Encoding', $this->config->get('purifier.encoding'));
 * $config->set('Cache.SerializerPath', $this->config->get('purifier.cachePath'));
 * if ( ! $this->config->get('purifier.finalize')) {
 *     $config->autoFinalize = false;
 * }
 * $config->loadArray($this->getConfig());
 *
 * You must NOT delete the default settings
 * anything in settings should be compacted with params that needed to instance HTMLPurifier_Config.
 *
 * @link http://htmlpurifier.org/live/configdoc/plain.html
 */

return [
    'encoding' => 'UTF-8',
    'finalize' => true,
    'ignoreNonStrings' => false,
    'cachePath' => storage_path('app/purifier'),
    'cacheFileMode' => 0755,
    'settings' => [
        'default' => [
            'HTML.Doctype' => 'HTML 4.01 Transitional',
            'HTML.Allowed' => 'div,b,strong,i,em,u,a[href|title],ul,ol,li,p[style],br,span[style],img[width|height|alt|src]',
            'CSS.AllowedProperties' => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align',
            'AutoFormat.AutoParagraph' => true,
            'AutoFormat.RemoveEmpty' => true,
        ],
        'test' => [
            'Attr.EnableID' => 'true',
        ],
        'youtube' => [
            'HTML.SafeIframe' => 'true',
            'URI.SafeIframeRegexp' => '%^(http://|https://|//)(www.youtube.com/embed/|player.vimeo.com/video/)%',
        ],
        'custom_definition' => [
            'id' => 'html5-definitions',
            'rev' => 1,
            'debug' => false,
            'elements' => [
                // http://developers.whatwg.org/sections.html
                ['section', 'Block', 'Flow', 'Common'],
                ['nav',     'Block', 'Flow', 'Common'],
                ['article', 'Block', 'Flow', 'Common'],
                ['aside',   'Block', 'Flow', 'Common'],
                ['header',  'Block', 'Flow', 'Common'],
                ['footer',  'Block', 'Flow', 'Common'],

                // Content model actually excludes several tags, not modelled here
                ['address', 'Block', 'Flow', 'Common'],
                ['hgroup', 'Block', 'Required: h1 | h2 | h3 | h4 | h5 | h6', 'Common'],

                // http://developers.whatwg.org/grouping-content.html
                ['figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common'],
                ['figcaption', 'Inline', 'Flow', 'Common'],

                // http://developers.whatwg.org/the-video-element.html#the-video-element
                ['video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', [
                    'src' => 'URI',
                    'type' => 'Text',
                    'width' => 'Length',
                    'height' => 'Length',
                    'poster' => 'URI',
                    'preload' => 'Enum#auto,metadata,none',
                    'controls' => 'Bool',
                ]],
                ['source', 'Block', 'Flow', 'Common', [
                    'src' => 'URI',
                    'type' => 'Text',
                ]],

                // http://developers.whatwg.org/text-level-semantics.html
                ['s',    'Inline', 'Inline', 'Common'],
                ['var',  'Inline', 'Inline', 'Common'],
                ['sub',  'Inline', 'Inline', 'Common'],
                ['sup',  'Inline', 'Inline', 'Common'],
                ['mark', 'Inline', 'Inline', 'Common'],
                ['wbr',  'Inline', 'Empty', 'Core'],

                // http://developers.whatwg.org/edits.html
                ['ins', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']],
                ['del', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']],
            ],
            'attributes' => [
                ['iframe', 'allowfullscreen', 'Bool'],
                ['table', 'height', 'Text'],
                ['td', 'border', 'Text'],
                ['th', 'border', 'Text'],
                ['tr', 'width', 'Text'],
                ['tr', 'height', 'Text'],
                ['tr', 'border', 'Text'],
            ],
        ],
        'custom_attributes' => [
            ['a', 'target', 'Enum#_blank,_self,_target,_top'],
            ['a', 'rel', 'Text'],
            ['a', 'download', 'Text'],
            ['div', 'data-video-type', 'Text'],
            ['div', 'data-video-id', 'Text'],
            ['div', 'data-original-url', 'Text'],
            ['div', 'data-file-type', 'Text'],
            ['div', 'data-platform', 'Text'],
            ['table', 'data-sortable', 'Text'],
            ['th', 'data-sortable-column', 'Text'],
            ['th', 'role', 'Text'],
            ['th', 'tabindex', 'Text'],
            ['input', 'placeholder', 'Text'],
            ['input', 'data-table-search', 'Text'],
            ['button', 'data-target', 'Text'],
            ['button', 'data-table-reset', 'Text'],
            ['button', 'data-lightbox-url', 'Text'],
            ['button', 'aria-label', 'Text'],
            ['iframe', 'allow', 'Text'],
            ['iframe', 'allowfullscreen', 'Bool'],
            ['iframe', 'loading', 'Text'],
            ['img', 'loading', 'Text'],
        ],
        'custom_elements' => [
            ['u', 'Inline', 'Inline', 'Common'],
            ['input', 'Inline', 'Empty', 'Common', [
                'type' => 'Enum#checkbox,text,hidden',
                'checked' => 'Bool',
                'disabled' => 'Bool',
                'name' => 'Text',
                'value' => 'Text',
            ]],
            ['button', 'Inline', 'Inline', 'Common', [
                'type' => 'Enum#button,submit,reset',
                'disabled' => 'Bool',
                'class' => 'Text',
                'data-target' => 'Text',
                'data-table-reset' => 'Text',
                'data-lightbox-url' => 'Text',
                'aria-label' => 'Text',
            ]],
            ['audio', 'Block', 'Optional: source, Flow', 'Common', [
                'controls' => 'Bool',
                'preload' => 'Enum#auto,metadata,none',
                'class' => 'Text',
            ]],
            ['video', 'Block', 'Optional: source, Flow', 'Common', [
                'controls' => 'Bool',
                'preload' => 'Enum#auto,metadata,none',
                'class' => 'Text',
                'width' => 'Text',
            ]],
            ['source', 'Inline', 'Empty', 'Common', [
                'src' => 'URI',
                'type' => 'Text',
            ]],
            ['details', 'Block', 'Flow', 'Common', [
                'class' => 'Text',
            ]],
            ['summary', 'Inline', 'Inline', 'Common', [
                'class' => 'Text',
            ]],
        ],
        'educational' => [
            'HTML.Doctype' => 'HTML 4.01 Transitional',
            'HTML.Allowed' => 'h1,h2,h3,h4,h5,h6,p,br,strong,b,em,i,u,s,del,ins,mark,sub,sup,ul,ol,li,dl,dt,dd,blockquote,pre,code,table[class|data-sortable],thead,tbody,tr[class],th[class|data-sortable-column|role|tabindex|style],td[class|style],img[src|alt|width|height|title|class|loading],a[href|title|target|rel|class|download],span[class],div[class|data-video-type|data-video-id|data-original-url|data-file-type|data-platform],hr,input[type|checked|disabled|class|placeholder|data-table-search],button[class|data-target|data-table-reset|data-lightbox-url|aria-label],iframe[src|title|frameborder|allow|allowfullscreen|loading|width|height],audio[controls|preload|class],video[controls|preload|class|width],source[src|type],details[class],summary[class]',
            'CSS.AllowedProperties' => 'color,background-color,font-family,font-size,font-weight,font-style,text-align,text-decoration,padding,margin,border,width,height',
            'AutoFormat.AutoParagraph' => true,
            'AutoFormat.RemoveEmpty' => false, // Don't remove empty elements for enhanced content
            'AutoFormat.Linkify' => false, // Disable auto-linkify to avoid conflicts with custom renderer
            'HTML.SafeIframe' => true, // Enable iframes for video embeds
            'URI.SafeIframeRegexp' => '%^(https?:)?//(www\.youtube\.com/embed/|player\.vimeo\.com/video/|www\.khanacademy\.org/)%',
            'URI.AllowedSchemes' => 'http,https,mailto',
            'Attr.AllowedFrameTargets' => '_blank,_self',
            'HTML.TargetBlank' => true,
        ],
    ],

];
