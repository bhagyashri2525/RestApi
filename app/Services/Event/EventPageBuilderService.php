<?php

namespace App\Services\Event;
use App\Models\PageTemplate;
use App\Services\Uploads\S3Service;
use Aws\Exception\AwsException;
use BaoPham\DynamoDb\RawDynamoDbQuery;
use Exception;
use Illuminate\Support\Facades\Log;

class EventPageBuilderService
{
    public function list($all = false)
    {
        $items = PageTemplate::where(['status' => true])->get();

        foreach ($items as $item) {
            $parts = explode('_', $item['slug']);
            $rightSide = $parts[1];
            $item['order'] = (int) $rightSide;
        }

        $items = collect($items)
            ->sortBy('order')
            ->values()
            ->all();
        return $items;
    }

    function getTemplateData($template)
    {
        //print_r($template);
        if ($template == 'standard_template_one') {
            return self::templateOneData();
        }
    }

    function templateOneData()
    {
        $speakers = [['id' => 1, 'name' => 'Faraz Ahmed', 'designation' => 'MD', 'description' => 'Faraz Ahmed is a Management Graduate in Econometrics and Marketing from Mumbai University and holds a Diploma in Web Technologies. Having started his career at Aptech Ltd., he has worked with organizations such as WebEx, MPOWER Asia and Sterling Infosystems Inc.', 'profile_image' => 'https://i.postimg.cc/yxfz7gxg/farazsir.jpg'], ['id' => 2, 'name' => 'Sumit Parekh', 'designation' => 'MD', 'description' => 'A postgraduate in Business Management from Mumbai University with enormous experience, Sumit Parekh has a successful track record in strategic business development and implementing marketing strategies, helping drive revenue growth in different industry verticals.', 'profile_image' => 'https://i.postimg.cc/NFGch7sv/sumitsir.jpg']];

        $clients = [['id' => 1, 'logo_url' => 'https://i.postimg.cc/2SSgzSNh/ARKADIN.jpg', 'alt' => 'ARKADIN'], ['id' => 2, 'logo_url' => 'https://i.postimg.cc/CLNWTfXw/Atos.jpg', 'alt' => 'Atos'], ['id' => 3, 'logo_url' => 'https://i.postimg.cc/QCZRsgjd/cipla.jpg', 'alt' => 'cipla'], ['id' => 4, 'logo_url' => 'https://i.postimg.cc/Wp0B8y7F/Marico.jpg', 'alt' => 'Marico'], ['id' => 5, 'logo_url' => 'https://i.postimg.cc/KvFdH599/MTNL.jpg', 'alt' => 'MTNL'], ['id' => 6, 'logo_url' => 'https://i.postimg.cc/mk3KrcrN/Pfizer.jpg', 'alt' => 'Pfizer']];

        $data = [];
        $data['template'] = 'standard_template_one';
        $data['logo_url'] = 'https://i.postimg.cc/mrqvpfqM/logo.png';
        $data['banner_url'] = 'https://i.postimg.cc/wj4Z3Y9P/computer.jpg';
        $data['overlay'] = ['title' => 'Virtual Event Demo', 'subtitle' => 'Tuesday 27 Oct 2020 | 5:00pm'];
        $data['header'] = 'Virtual Cost Effective Digital Transformation for a post-COVID Future of Work';
        $data['description'] = 'StreamOn Technologies Pvt. Ltd. is a diverse collective of tech enthusiasts, designers, problem solvers & engineers intent on redefining the way people and businesses communicate and collaborate. We believe in the power of making great connections and forming strong human bonds that enhance productivity and a sense of community. The work we do at StreamOn strives to make collaboration tools accessible, convenient and better than they ever have been. Here in our office in Mumbai, we seek novel and unthought-of ideas and associations to bring our obsessions to connect to life.';

        $data['client']['title'] = 'Our Clients';
        $data['client']['data'] = $clients;

        $data['speaker']['title'] = 'Featured Speakers';
        $data['speaker']['data'] = $speakers;

        $data['footer'] = '© 2023 Login Forms. All rights reserved | Design by Streamonindia.com';

        $loginForm = [['id' => 1, 'name' => 'email', 'input_type' => 'email', 'input_label' => 'Please enter registered email', 'status' => true], ['id' => 2, 'name' => 'password', 'input_type' => 'password', 'input_label' => 'Password', 'status' => false]];

        $countries = null;

        $form = [
            'form_section_title' => 'Registration',

            'fields' => [
                ['id' => 1, 'label' => 'Fullname', 'name' => 'full_name', 'input_label' => 'Fullname', 'input_type' => 'text', 'status' => true, 'is_required' => true],
                ['id' => 2, 'label' => 'Email', 'name' => 'email', 'input_label' => 'Email', 'input_type' => 'text', 'status' => true, 'is_required' => true],
                ['id' => 3, 'label' => 'Job Title', 'name' => 'job_title', 'input_label' => 'Job Title', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 4, 'label' => 'Company Name', 'name' => 'company_name', 'input_label' => 'Company Name', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 5, 'label' => 'Location', 'name' => 'location', 'input_label' => 'Location', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 6, 'label' => 'Contact', 'name' => 'contact', 'input_label' => 'Contact', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 7, 'label' => 'Password', 'name' => 'password', 'input_label' => 'Password', 'input_type' => 'moderate', 'status' => false, 'is_required' => false],

                ['id' => 8, 'label' => 'Firstname', 'name' => 'firstname', 'input_label' => 'Firstname', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 9, 'label' => 'Lastname', 'name' => 'lastname', 'input_label' => 'Lastname', 'input_type' => 'text', 'status' => false, 'is_required' => false],

                ['id' => 10, 'label' => 'Address 1', 'name' => 'address1', 'input_label' => 'Address 1', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 11, 'label' => 'Address 2', 'name' => 'address2', 'input_label' => 'Address 2', 'input_type' => 'text', 'status' => false, 'is_required' => false],

                ['id' => 12, 'label' => 'Country', 'name' => 'country', 'input_label' => 'Country', 'input_type' => 'text', 'status' => false, 'is_required' => false, 'options' => ['list' => $countries]],
                ['id' => 13, 'label' => 'State', 'name' => 'state', 'input_label' => 'State', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 14, 'label' => 'City', 'name' => 'city', 'input_label' => 'City', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 15, 'label' => 'Zip', 'name' => 'zip', 'input_label' => 'Zip', 'input_type' => 'text', 'status' => false, 'is_required' => false],

                ['id' => 16, 'label' => 'Workphone', 'name' => 'workphone', 'input_label' => 'Workphone', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 17, 'label' => 'Company Industry', 'name' => 'company_industry', 'input_label' => 'Company Industry', 'input_type' => 'text', 'status' => false, 'is_required' => false],

                ['id' => 18, 'label' => 'Company Size', 'name' => 'company_size', 'input_label' => 'Company Size', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 19, 'label' => 'Event Mail', 'name' => 'event_mail', 'input_label' => 'Event Mail', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 20, 'label' => 'Marketing Email', 'name' => 'marketing_email', 'input_label' => 'Marketing Email', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 21, 'label' => 'Partner Ref', 'name' => 'partner_ref', 'input_label' => 'Partner Ref', 'input_type' => 'text', 'status' => false, 'is_required' => false],

                ['id' => 22, 'label' => 'Custom 1', 'name' => 'custom1', 'input_label' => 'Custom 1', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 23, 'label' => 'Custom 2', 'name' => 'custom2', 'input_label' => 'Custom 2', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 24, 'label' => 'Custom 3', 'name' => 'custom3', 'input_label' => 'Custom 3', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 25, 'label' => 'Custom 4', 'name' => 'custom4', 'input_label' => 'Custom 4', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 26, 'label' => 'Custom 5', 'name' => 'custom5', 'input_label' => 'Custom 5', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 27, 'label' => 'Custom 6', 'name' => 'custom6', 'input_label' => 'Custom 6', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 28, 'label' => 'Custom 7', 'name' => 'custom7', 'input_label' => 'Custom 7', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 29, 'label' => 'Custom 8', 'name' => 'custom8', 'input_label' => 'Custom 8', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 30, 'label' => 'Custom 9', 'name' => 'custom9', 'input_label' => 'Custom 9', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 31, 'label' => 'Custom 10', 'name' => 'custom10', 'input_label' => 'Custom 10', 'input_type' => 'text', 'status' => false, 'is_required' => false],

                ['id' => 32, 'label' => 'Url', 'name' => 'url', 'input_label' => 'Url', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 33, 'label' => 'Home Phone', 'name' => 'home_phone', 'input_label' => 'Home Phone', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 34, 'label' => 'Fax', 'name' => 'fax', 'input_label' => 'Fax', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 35, 'label' => 'Other', 'name' => 'other', 'input_label' => 'Other', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 36, 'label' => 'Notes', 'name' => 'notes', 'input_label' => 'Notes', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 37, 'label' => 'Timezone Code', 'name' => 'timezone_code', 'input_label' => 'Timezone Code', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 38, 'label' => 'Operating System', 'name' => 'operating_system', 'input_label' => 'Operating System', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 39, 'label' => 'Event User IP', 'name' => 'event_user_ip', 'input_label' => 'Event User IP', 'input_type' => 'text', 'status' => false, 'is_required' => false],
                ['id' => 40, 'label' => 'Browser', 'name' => 'browser', 'input_label' => 'Browser', 'input_type' => 'text', 'status' => false, 'is_required' => false],
            ],

            'section_visibility' => [
                ['id' => 1, 'key' => 'already_register', 'label' => 'Already Register', 'status' => true, 'form_fields' => $loginForm, 'title' => 'Already Register ?'],
                // ['id' => 2,'key' => 'artwork','label' => 'Artwork','status' => false],
                // ['id' => 3,'key' => 'agenda','label' => 'Agenda','status' => false],
            ],
        ];

        $theme = ['primary_color' => '#007BFF'];

        $data['theme'] = $theme;
        $data['form'] = $form;

        $upper = ['id' => 1, 'section' => 'upper', 'label' => 'Page Upper Section', 'status' => 'show'];
        $lower = ['id' => 2, 'section' => 'lower', 'label' => 'Page Lower Section', 'status' => 'hide'];
        $logo = ['id' => 3, 'section' => 'logo', 'label' => 'Logo', 'status' => 'show'];
        $overlay = ['id' => 4, 'section' => 'overlay', 'label' => 'Overlay', 'status' => 'show'];
        $title = ['id' => 5, 'section' => 'title', 'label' => 'Title', 'status' => 'show'];
        $description = ['id' => 6, 'section' => 'description', 'label' => 'Description', 'status' => 'show'];
        $logoSection = ['id' => 7, 'section' => 'client-logo-section', 'label' => 'Client Logo Section', 'status' => 'show'];
        $speakerSection = ['id' => 8, 'section' => 'speaker-section', 'label' => 'Speaker Section', 'status' => 'show'];
        $footerSection = ['id' => 9, 'section' => 'footer-section', 'label' => 'Footer', 'status' => 'show'];

        $data['page_visibility'][] = $upper;
        $data['page_visibility'][] = $lower;
        $data['page_visibility'][] = $logo;
        $data['page_visibility'][] = $overlay;
        $data['page_visibility'][] = $title;
        $data['page_visibility'][] = $description;
        $data['page_visibility'][] = $logoSection;
        $data['page_visibility'][] = $speakerSection;
        $data['page_visibility'][] = $footerSection;

        $page = ['bg_color' => '#FFFFFF'];

        // meta
        $meta = [
            'title' => 'Event Register Page',
            'meta_tags' => null,
        ];

        $data['meta'] = $meta;
        $data['page'] = $page;

        return $data;
    }

    public function prepareForRenderPage($regeneratedElement, $companyId, $eventId, $companySlug, $eventSlug)
    {
        // Load the form modal HTML content
        $formModalHtmlContent = file_get_contents('https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/formBuilder/form-builder-modal.html');
        $contextMenuContent = file_get_contents("https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/formBuilder/context-menu.html");

        libxml_use_internal_errors(true);
        // Create a new DOMDocument for the form modal HTML
        $formModalDom = new \DOMDocument();
        $formModalDom->loadHTML($formModalHtmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $contextMenuDom = new \DOMDocument();
        $contextMenuDom->loadHTML($contextMenuContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        // Handle any errors that occurred during loading
        $errors = libxml_get_errors();
        foreach ($errors as $error) {
            // Handle the error as needed (e.g., log or print)
            //echo "Error: {$error->message}\n";
        }
        libxml_clear_errors();

        // Create a <div> element to hold the form modal content
        $divElement = $regeneratedElement->ownerDocument->createElement('div');
        $divElement->setAttribute('class', 'streamon-html-code');

        // Append the form modal's child nodes to the <div> element
        foreach ($formModalDom->childNodes as $node) {
            $divElement->appendChild($regeneratedElement->ownerDocument->importNode($node, true));
        }

        // Append the form modal's child nodes to the <div> element
        foreach ($contextMenuDom->childNodes as $node) {
            $divElement->appendChild($regeneratedElement->ownerDocument->importNode($node, true));
        }

        // Append the <div> element to the <body> tag of $regeneratedElement
        $bodyElement = $regeneratedElement->getElementsByTagName('body')->item(0);
        $bodyElement->appendChild($divElement);

        // URLs of scripts to be added
        $scriptUrls = [
            'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/formBuilder/form-builder.min.js',
            'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/formBuilder/form-render.min.js',
            'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/formBuilder/index.js',
            //asset('js/functions/formBuilder.js'),
            'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/streamon-page-editor-plugin.js',
            'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/streamon-custom.js',
            asset('js/functions/api.js'),
            //asset('js/functions/index.js'),
            //'https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js',
        ];

        // Iterate through script URLs and create <script> elements
        foreach ($scriptUrls as $url) {
            $scriptTag = $regeneratedElement->ownerDocument->createElement('script');
            $scriptTag->setAttribute('src', $url);
            $bodyElement->appendChild($scriptTag);
        }

        $scriptVariables = null;
        $scriptNodes = $bodyElement->getElementsByTagName('script');

        // Check if the variables are already declared
        foreach ($scriptNodes as $scriptNode) {
            if (strpos($scriptNode->textContent, 'var eventId') !== false) {
                $scriptVariables = $scriptNode;
                break;
            }
        }

        // If variables are not declared, create and append the <script> element
        if ($scriptVariables === null) {
            $scriptVariables = $regeneratedElement->ownerDocument->createElement('script');

            $jsVarsString = "var eventId = \"{$eventId}\"; var companyId = \"{$companyId}\"; var companySlug = \"{$companySlug}\"; var eventSlug = \"{$eventSlug}\";";

            //$scriptVariables->textContent = 'var eventId = "' . $eventId . '"; var companyId = "' . $companyId . '"; var companySlug = "' . $companySlug .;
            $scriptVariables->textContent = $jsVarsString;
            $bodyElement->appendChild($scriptVariables);
        }

        $eventPublicJsFolderPath = '/js/event/' . $companySlug . '/' . $eventSlug;
        $assetUrl = asset($eventPublicJsFolderPath . '/index.js');
        $htmlContent = (new EventPageBuilderService())->removeJsScripts([$assetUrl], $regeneratedElement, $companySlug, $eventSlug);

        // Convert the modified DOMDocument back to HTML
        $htmlContent = $regeneratedElement->ownerDocument->saveHTML($regeneratedElement);

        return $htmlContent;
    }

    public function prepareForSavePageData($htmlContent, $companySlug, $eventSlug)
    {
        try {
            $bodyElement = $htmlContent->getElementsByTagName('body')->item(0);
            $headElement = $htmlContent->getElementsByTagName('head')->item(0);

            $divElementsToRemove = [];
            $scriptElementsToRemove = [];
            $linkElementsToRemove = [];

            // Find <div> elements with class "streamon-html-code"
            foreach ($bodyElement->childNodes as $node) {
                if ($node->nodeName === 'div' && $node->getAttribute('class') === 'streamon-html-code') {
                    $divElementsToRemove[] = $node;
                }
            }

            // Remove the <div> elements
            foreach ($divElementsToRemove as $divElement) {
                $bodyElement->removeChild($divElement);
            }

            // Find and remove script elements with specific src URLs
            $scriptUrls = [
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/formBuilder/form-builder.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/formBuilder/form-render.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/formBuilder/index.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/streamon-page-editor-plugin.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/streamon-custom.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/froala_editor.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/align.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/code_beautifier.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/code_view.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/colors.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/emoticons.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/draggable.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/font_size.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/font_family.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/image.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/image_manager.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/line_breaker.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/quick_insert.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/link.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/lists.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/paragraph_format.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/paragraph_style.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/video.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/table.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/url.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/file.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/entities.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/inline_style.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/save.min.js',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/js/plugins/fullscreen.min.js',
                asset('js/functions/api.js'),
                asset('js/functions/index.js'),
                asset('js/functions/formBuilder.js'),
                'http://localhost:8000/js/functions/index.js',
                'http://localhost:8000/js/functions/api.js',
            ];

            foreach ($scriptUrls as $url) {
                $scriptNodes = $htmlContent->getElementsByTagName('script');
                foreach ($scriptNodes as $scriptNode) {
                    if ($scriptNode->hasAttribute('src') && $scriptNode->getAttribute('src') === $url) {
                        $scriptElementsToRemove[] = $scriptNode;
                    }
                }
            }

            foreach ($scriptElementsToRemove as $scriptElement) {
                $parent = $scriptElement->parentNode;
                if ($parent !== null) {
                    $parent->removeChild($scriptElement);
                }
            }

            // Find and remove link elements with specific href URLs
            $linkUrls = [
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/formBuilder/form-builder-modal.css',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/css/froala_editor.css',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/css/froala_style.css',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/css/plugins/code_view.css',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/css/plugins/colors.css',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/css/plugins/image_manager.css',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/css/plugins/image.css',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/css/plugins/line_breaker.css',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/css/plugins/quick_insert.css',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/css/plugins/table.css',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/css/plugins/file.css',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/css/plugins/char_counter.css',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/css/plugins/video.css',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/css/plugins/emoticons.css',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/css/plugins/fullscreen.css',
                'https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/plugins/froala/streamon-custom.css',
                // Add more link URLs to remove here
            ];

            foreach ($linkUrls as $url) {
                $linkNodes = $htmlContent->getElementsByTagName('link');
                foreach ($linkNodes as $linkNode) {
                    if ($linkNode->hasAttribute('href') && $linkNode->getAttribute('href') === $url) {
                        $linkElementsToRemove[] = $linkNode;
                    }
                }
            }

            foreach ($linkElementsToRemove as $linkElement) {
                $headElement->removeChild($linkElement);
            }

            $jsLinks = (new EventPageBuilderService())->generateEventPageJsLinks($companySlug, $eventSlug);
            $htmlContent = (new EventPageBuilderService())->addJsScripts($jsLinks, $htmlContent);

            $output = $htmlContent->ownerDocument->saveHTML($htmlContent);

            return ['dom_element' => $htmlContent, 'output' => $output];
        } catch (\Exception $e) {
            #print_r($e->getMessage());
            #print_r($e->getLine());
            return false;
        }
    }

    public function generateEventPageJsLinks($companyNameSlug, $eventSlug)
    {
        $eventPublicJsFolderPath = '/js/event/' . $companyNameSlug . '/' . $eventSlug;
        $assetUrl = asset($eventPublicJsFolderPath . '/index.js');
        //$apiJs = asset('js/functions/api.js');
        return [$assetUrl];
    }

    public function addJsScripts($jsLinks, $htmlDomDocumentElement)
    {
        // Get the <body> element
        $bodyElement = $htmlDomDocumentElement->getElementsByTagName('body')->item(0);

        // Append script tags for the provided JavaScript URLs
        foreach ($jsLinks as $url) {
            $scriptTag = $htmlDomDocumentElement->ownerDocument->createElement('script');
            $scriptTag->setAttribute('src', $url);
            $bodyElement->appendChild($scriptTag);
        }

        // Return the updated HTML DOMDocument element
        return $htmlDomDocumentElement;
    }

    public function removeJsScripts($jsUrls, $htmlDomDocument)
    {
        // Find and remove script elements with specific src URLs from the <body> element
        $scriptNodes = $htmlDomDocument->getElementsByTagName('script');
        $scriptElementsToRemove = [];
        foreach ($scriptNodes as $scriptNode) {
            if ($scriptNode->hasAttribute('src') && in_array($scriptNode->getAttribute('src'), $jsUrls)) {
                $scriptElementsToRemove[] = $scriptNode;
            }
        }

        foreach ($scriptElementsToRemove as $scriptElement) {
            $parent = $scriptElement->parentNode;
            if ($parent !== null) {
                $parent->removeChild($scriptElement);
            }
        }

        // Return the updated HTML DOMDocument
        return $htmlDomDocument;
    }

    public function addCssLinks($cssLinks, $htmlDomDocumentElement)
    {
        // Get the <head> element
        $headElement = $htmlDomDocumentElement->getElementsByTagName('head')->item(0);

        // Append link tags for the provided CSS URLs
        foreach ($cssLinks as $url) {
            $linkTag = $htmlDomDocumentElement->createElement('link');
            $linkTag->setAttribute('rel', 'stylesheet');
            $linkTag->setAttribute('type', 'text/css');
            $linkTag->setAttribute('href', $url);
            $headElement->appendChild($linkTag);
        }

        // Return the updated HTML DOMDocument element
        return $htmlDomDocumentElement;
    }

    public function removeCssLinks($cssUrls, $htmlDomDocument)
    {
        // Find and remove link elements with specific href URLs from the <head> element
        $linkNodes = $htmlDomDocument->getElementsByTagName('link');
        $linkElementsToRemove = [];
        foreach ($linkNodes as $linkNode) {
            if ($linkNode->hasAttribute('href') && in_array($linkNode->getAttribute('href'), $cssUrls)) {
                $linkElementsToRemove[] = $linkNode;
            }
        }

        foreach ($linkElementsToRemove as $linkElement) {
            $parent = $linkElement->parentNode;
            if ($parent !== null) {
                $parent->removeChild($linkElement);
            }
        }

        // Return the updated HTML DOMDocument
        return $htmlDomDocument;
    }

    public function addClasses($classNames, $htmlDomDocumentElement)
    {
        // Get the elements based on class names
        $elements = $htmlDomDocumentElement->getElementsByTagName('*');
        foreach ($elements as $element) {
            foreach ($classNames as $className) {
                $currentClass = $element->getAttribute('class');
                $newClass = $currentClass . ' ' . $className;
                $element->setAttribute('class', $newClass);
            }
        }

        // Return the updated HTML DOMDocument element
        return $htmlDomDocumentElement;
    }

    public function removeClasses($classNames, $htmlDomDocument)
    {
        // Get the elements based on class names
        $elements = $htmlDomDocument->getElementsByTagName('*');
        foreach ($elements as $element) {
            foreach ($classNames as $className) {
                $currentClass = $element->getAttribute('class');
                $newClass = str_replace($className, '', $currentClass);
                $element->setAttribute('class', $newClass);
            }
        }

        // Return the updated HTML DOMDocument
        return $htmlDomDocument;
    }

    public function findIndexHtml($bucketName, $directoryPath) {
        try {
            $s3Client = (new S3Service)->S3ClientObject();
            $objects = $s3Client->listObjectsV2([
                'Bucket' => $bucketName,
                'Prefix' => $directoryPath,
            ]);
            $htmlFiles = [];

            if ($objects && isset($objects['Contents']) && count($objects['Contents'])) {
                foreach ($objects['Contents'] as $object) {
                    if (pathinfo($object['Key'], PATHINFO_EXTENSION) === 'html') {
                        $htmlFiles[] = $object['Key'];
                    }
                }
            }
    
            return $htmlFiles;
        } catch (AwsException $e) {
            // Handle exceptions if needed
            // echo "Error: " . $e->getMessage();
            return null;
        }
    }
    
    public function templatesFormLayout($template, $data, $wants="elem") {
        try {
            $formLayout = "";
            // $data = is_string($data) ? json_decode($data, true) : $data;
            switch($template) {
                case 'option_1':
                case 'option_2':
                case 'option_3':
                case 'option_4':
                case 'option_5':
                    $div = "<div class=\"form-group\">";
                    if(in_array($template, ["option_2", "option_3"])) {
                        $div = "<div class=\"form-wrap\">";
                    } elseif ($template == "option_5") {
                        $div = "<div class=\"col-md-4\"><div class=\"form-wrap\">";
                    } elseif ($template == "option_1") {
                        $div = "<div class=\"mb-3\">";
                    }
                    foreach ($data as $key => $value) {
                        // dd($value);
                        if($key == "login") {
                            if($wants == "elem") {
                                $formLayout .= '<h4 class="text-center">Sign In</h4>';    
                            }
                            $formLayout .= '
                                <form class="rd-mailform text-left" id="loginForm" action="">
                            ';
                        } else {
                            if($wants == "elem") {
                                $formLayout .= '<h4 class="text-center">Sign Up</h4>';    
                            }
                            $formLayout .= '
                                <form class="rd-mailform text-left" id="registerForm" action="">
                            ';
                        }

                        if($template == "option_5") {
                            $formLayout .= "<div class=\"row row-small\">";
                        }
                        $fields = json_decode($value, true);
                        foreach ($fields as $i => $field) {
                            $submitButton = "";
                            
                            $type = isset($field['type']) ? $field['type'] : "text";
                            $subtype = isset($field['subtype']) ? $field['subtype'] : $type;
                            $required = isset($field['required']) ? ($field['required'] == true ? "required" : "") : null;
                            $label = isset($field['label']) ? $field['label'] : null;
                            $placeholder = isset($field['placeholder']) ? $field['placeholder'] : null;
                            $className = isset($field['className']) ? $field['className'] : null;
                            $name = isset($field['name']) ? $field['name'] : null;
                            $value = isset($field['value']) ? $field['value'] : "Submit";
                            $id = isset($field['id']) ? $field['id'] : null;
                            // print_r($subtype)."\n\n";
                            if($subtype == "submit") {
                                if($wants == "flds") {
                                    continue;
                                }
                                if($key == "login") {
                                    $formTarget = "form=\"loginForm\"";
                                } else {
                                    $formTarget = "form=\"registerForm\"";
                                }
                                switch($template) {
                                    case "option_1":
                                        $submitButton = "
                                            <div class=\"text-left pb-3\">
                                                <button class=\"btn btn-primary novi-background\" id=\"submit-btnl\" type=\"submit\" $formTarget dir=\"ltr\" data-style=\"expand-left\">
                                                    $label
                                                </button>
                                            </div>
                                        ";
                                        break;
                                    case "option_2":
                                        $submitButton = "
                                            <div class=\"form-wrap stick-to-bottom\">
                                                <button class=\"btn btn-lg btn-default novi-background\" type=\"submit\" $formTarget>
                                                    $label
                                                </button>
                                            </div>
                                        ";
                                        break;
                                    case "option_3":
                                        $submitButton = "
                                                <button class=\"button button-primary\" type=\"submit\" $formTarget>
                                                    $label
                                                </button>
                                        ";
                                        break;
                                    case "option_4":
                                        $submitButton = "
                                            <button class=\"btn btn-lg btn-default btn-square\" type=\"submit\" $formTarget>
                                                $label
                                            </button>
                                        ";
                                        break;
                                    case "option_5":
                                        $submitButton = "
                                            <div class=\"col-sm-12 text-center\">
                                                <button class=\"btn btn-md btn-primary\" type=\"submit\" $formTarget>$label</button>
                                            </div>
                                        ";
                                        break;
                                    default:
                                        break;
                                }
                            } elseif(in_array($type, ["radio", 'checkbox', 'checkbox-group', 'radio-group', 'select'])) {
                                if(in_array($type, ['checkbox-group', 'radio-group'])) {
                                    $type = explode("-", $type)[0];
                                }

                                $formLayout .= $div;
                                $formLayout .= "<label class=\"form-label\" for=\"$name\">$label</label>";
                                $values = isset($field['values']) ? $field['values'] : [];
                                if($type === "select") {
                                    $dataWidth = "";
                                    if($template == "option_1") {
                                        $dataWidth = "data-width=\"100%\"";
                                    }
                                    $formLayout .= "<select class=\"form-select form-control select-filter form-input\" 
                                        name=\"$name\" id=\"$name\" aria-label=\"$label\" style=\"color: inherit;\" $required $dataWidth>";
                                    foreach ($values as $key => $inpValue) {
                                        $formLayout .= "<option value=\"".$inpValue['value']."\">".$inpValue['label']."</option>";
                                    }
                                    $formLayout .= "</select>";
                                } else {
                                    $name = ($type == 'checkbox') ? $name : $name."-".$key;
                                    foreach ($values as $key => $inpValue) {
                                        $formLayout .= "
                                            <div class=\"form-check\">
                                                <input class=\"form-check-input $className\" type=\"$type\" name=\"$name\" value=\"".$inpValue['value']."\" id=\"".($inpValue['value'].'-'.$name)."\">
                                                <label class=\"form-check-label\" for=\"".($inpValue['value'].'-'.$name)."\">".
                                                    $inpValue['label'].
                                                "</label>
                                            </div>
                                        ";
                                    }
                                }
                                
                                if($template == "option_5") {
                                    $formLayout .= "</div></div>";
                                } else {
                                    $formLayout .= "</div>";
                                }
                            } else {
                                $formLayout .= "
                                    $div
                                ";
                                if($template == "option_1") {
                                    $formLayout .= "
                                        <label class=\"form-label\" for=\"$name\">$label</label>
                                        <input
                                            class=\"form-input $className form-control\" 
                                            id=\"$name\" type=\"$subtype\"
                                            name=\"$name\" placeholder=\"$placeholder\" $required autocomplete=\"off\" parsley-type=\"$subtype\"/>
                                        <span class=\"form-validation\"></span>
                                    ";
                                } elseif ($template == "option_2" || $template == "option_3") {
                                    $formLayout .= "
                                        <input
                                            class=\"form-input $className form-control\" 
                                            id=\"$name\" type=\"$subtype\"
                                            name=\"$name\" placeholder=\"$placeholder\" $required autocomplete=\"off\" parsley-type=\"$subtype\"/>
                                        <span class=\"form-validation\"></span>
                                    ";
                                } else {
                                    $formLayout .= "
                                        <label class=\"form-label\" for=\"$name\">$label</label>
                                        <input
                                            class=\"form-input $className form-control\" 
                                            id=\"$name\" type=\"$subtype\" parsley-type=\"$subtype\"
                                            name=\"$name\" $required autocomplete=\"off\"/>
                                        <span class=\"form-validation\"></span>
                                    ";
                                }   
                                if($template == "option_5") {
                                    $formLayout .= "</div></div>";
                                } else {
                                    $formLayout .= "</div>";
                                }
                            }
                        }
                        if($template == "option_5") {
                            $formLayout .= "</div>";
                        }
                        $formLayout .= "</form>";
                        if($wants == "elem") {
                            $formLayout .= $submitButton;
                        }
                    }
                    break;
                case "default":
                    break;
            }
            // dd($formLayout);
            return $formLayout;
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                print_r('line no: ', $e->getLine());
                Log::error($e->getMessage());
            }
            print_r($e->getMessage());
            return null;
        }
    }

    public function preparePageBuilderResources($companySlug, $eventSlug, $templateSlug) {
        try {
            $s3Client = (new S3Service)->S3ClientObject();
            $objects = $s3Client->listObjectsV2([
                'Bucket' => env('AWS_BUCKET'), // Use the same bucket name for source and destination
                'Prefix' => env('AWS_DEFAULT_FOLDER') . "/editor/template/$templateSlug/"
            ]);
            // dd($objects['Contents']);
            if(!empty($objects) && isset($objects['Contents'])) {
                $sourcePrefix = env('AWS_DEFAULT_FOLDER') . "/editor/template/$templateSlug/";
                $comDestPrefix = env('AWS_DEFAULT_FOLDER') . "/editor/data/$companySlug/templates/$templateSlug/";
                $eventDestPrefix = env('AWS_DEFAULT_FOLDER') . "/editor/data/$companySlug/$eventSlug/$templateSlug/";
                foreach ($objects['Contents'] as $object) {
                    $objectKey = $object['Key'];

                    $comDestKey = str_replace($sourcePrefix, $comDestPrefix, $objectKey);
                    $eventDestKey = str_replace($sourcePrefix, $eventDestPrefix, $objectKey);
                    // Copy the object to the destination with the new key
                    $s3Client->copyObject([
                        'Bucket' => env('AWS_BUCKET'),
                        'CopySource' => env('AWS_BUCKET') . '/' . $objectKey,
                        'Key' => $comDestKey
                    ]);

                    $s3Client->copyObject([
                        'Bucket' => env('AWS_BUCKET'),
                        'CopySource' => env('AWS_BUCKET') . '/' . $objectKey,
                        'Key' => $eventDestKey
                    ]);
                }
            }
            return true;
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                print_r('line no: ', $e->getLine());
                Log::error($e->getMessage());
            }
            print_r($e->getMessage());
            return null;
        }
    }

    public function preparePageBuilderResourcesAndProjectJson($companySlug, $eventSlug, $templateSlug) {
        try {
            // dd($companySlug, $eventSlug, $templateSlug);
            $s3Client = (new S3Service)->S3ClientObject();
            $objects = $s3Client->listObjectsV2([
                'Bucket' => env('AWS_BUCKET'), // Use the same bucket name for source and destination
                'Prefix' => env('AWS_DEFAULT_FOLDER') . "/editor/template/$templateSlug/"
            ]);
            // Log::debug("Object Key: ". json_encode($objects));
            // dd($objects['Contents']);
            if(!empty($objects) && isset($objects['Contents'])) {
                $sourcePrefix = env('AWS_DEFAULT_FOLDER') . "/editor/template/$templateSlug/";
                $comDestPrefix = env('AWS_DEFAULT_FOLDER') . "/editor/data/$companySlug/templates/$templateSlug/";
                $eventDestPrefix = env('AWS_DEFAULT_FOLDER') . "/editor/data/$companySlug/$eventSlug/$templateSlug/";
                $comDir = env('AWS_BASE_URL')."/".env('AWS_DEFAULT_FOLDER') . "/editor/data/$companySlug/templates/$templateSlug/";
                $eventDir = env('AWS_BASE_URL')."/".env('AWS_DEFAULT_FOLDER') . "/editor/data/$companySlug/$eventSlug/$templateSlug/";
                foreach ($objects['Contents'] as $object) {
                    $objectKey = $object['Key'];
                    // Log::debug("Object Key: ".$objectKey);
                    $projectJsonArr = [];
                    if (strpos($objectKey, "project.json") !== false) {
                        try{
                            $projectJsonArr = json_decode(file_get_contents(env('AWS_BASE_URL')."/".$objectKey), true);
                        } catch (Exception $e) {
                            $projectJsonArr = [];
                        }
                        if(is_string($projectJsonArr)) {
                            $projectJsonArr = json_decode($projectJsonArr, true);
                        }
                        $compSlugJson = $projectJsonArr;
                        $eventSlugJson = $projectJsonArr;
                        if(isset($projectJsonArr['dir'])) {
                            $compSlugJson['dir'] = $comDir; 
                            $eventSlugJson['dir'] = $eventDir; 
                        }
                        if(isset($projectJsonArr['mediaLibrary'])) {
                            foreach($compSlugJson['mediaLibrary']['items'] as $key => $item) {
                                $compSlugJson['mediaLibrary']['items'][$key]['thumbnail'] = $comDir."novi/media/thumbnails/".$item['original'];
                            }
                            foreach($eventSlugJson['mediaLibrary']['items'] as $key => $item) {
                                $eventSlugJson['mediaLibrary']['items'][$key]['thumbnail'] = $eventDir."novi/media/thumbnails/".$item['original'];
                            }
                        }
                        $companyJson = json_encode($compSlugJson);
                        $eventJson = json_encode($eventSlugJson);
                        $s3Client->putObject([
                            'Bucket' => env('AWS_BUCKET'),
                            'Key' => $comDestPrefix."project.json",
                            'Body' => $companyJson,
                            'ContentType' => 'application/json',
                            'ACL' => 'public-read'
                        ]);
                        $s3Client->putObject([
                            'Bucket' => env('AWS_BUCKET'),
                            'Key' => $eventDestPrefix."project.json",
                            'Body' => $eventJson,
                            'ContentType' => 'application/json',
                            'ACL' => 'public-read'
                        ]);
                        continue;
                    }

                    $comDestKey = str_replace($sourcePrefix, $comDestPrefix, $objectKey);
                    $eventDestKey = str_replace($sourcePrefix, $eventDestPrefix, $objectKey);
                    // Copy the object to the destination with the new key
                    $s3Client->copyObject([
                        'Bucket' => env('AWS_BUCKET'),
                        'CopySource' => env('AWS_BUCKET') . '/' . $objectKey,
                        'Key' => $comDestKey
                    ]);

                    $s3Client->copyObject([
                        'Bucket' => env('AWS_BUCKET'),
                        'CopySource' => env('AWS_BUCKET') . '/' . $objectKey,
                        'Key' => $eventDestKey
                    ]);
                }
            }
            return true;
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                print_r('line no: ', $e->getLine());
                Log::error($e->getMessage());
            }
            print_r($e->getMessage());
            return null;
        }
    }

    public function updateProjectJson($companySlug, $eventSlug, $templateSlug) {
        try {
            $s3Client = (new S3Service)->S3ClientObject();
            $bucketName = env('AWS_BUCKET');
            $defaultFolder = env('AWS_DEFAULT_FOLDER');
            $companyProjectJsonPath = env('AWS_DEFAULT_FOLDER') . "/editor/data/$companySlug/templates/$templateSlug/project.json";
            $eventProjectJsonPath = env('AWS_DEFAULT_FOLDER') . "/editor/data/$companySlug/$eventSlug/$templateSlug/project.json";
            // $comObj = env('AWS_DEFAULT_FOLDER') . "/editor/data/$companySlug/templates/$templateSlug/";
            // $eventObj = env('AWS_DEFAULT_FOLDER') . "/editor/data/$companySlug/$eventSlug/$templateSlug/";
            // dd($companyProjectJsonPath);
            if($s3Client->doesObjectExist($bucketName, $companyProjectJsonPath)) {
                $comPath = env('AWS_USE_PUBLIC_DEFAULT_URL'). "$companySlug/templates/$templateSlug/project.json";
                $dir = dirname($comPath)."/";
                $comPJ = json_decode(file_get_contents($comPath), true);
                if(is_string($comPJ)) {
                    $comPJ = json_decode($comPJ, true);
                }
                    
                if(isset($compJ['dir'])) {
                    $comPJ['dir'] = $dir;
                }
                if(isset($comPJ['mediaLibrary'])) {
                    foreach($comPJ['mediaLibrary']['items'] as $key => $item) {
                        $comPJ['mediaLibrary']['items'][$key]['thumbnail'] = $dir."novi/media/thumbnails/".$item['original'];
                    }
                }
                
                $companyJson = json_encode($comPJ);
                $s3Client->putObject([
                    'Bucket' => env('AWS_BUCKET'),
                    'Key' => $companyProjectJsonPath,
                    'Body' => $companyJson,
                    'ContentType' => 'application/json',
                    'ACL' => 'public-read'
                ]);
            }
            if($s3Client->doesObjectExist($bucketName, $eventProjectJsonPath)) {
                $eventPath = env('AWS_USE_PUBLIC_DEFAULT_URL'). "$companySlug/$eventSlug/$templateSlug/project.json";
                $dir = dirname($eventPath)."/";
                $eventPJ = json_decode(file_get_contents($eventPath), true);
                if(is_string($eventPJ)) {
                    $eventPJ = json_decode($eventPJ, true);
                }   
                if(isset($eventPJ['dir'])) {
                    $eventPJ['dir'] = $dir; 
                }
                if(isset($eventPJ['mediaLibrary'])) {
                    foreach($eventPJ['mediaLibrary']['items'] as $key => $item) {
                        $eventPJ['mediaLibrary']['items'][$key]['thumbnail'] = $dir."novi/media/thumbnails/".$item['original'];
                    }
                }
                $eventJson = json_encode($eventPJ);
                $s3Client->putObject([
                    'Bucket' => env('AWS_BUCKET'),
                    'Key' => $eventProjectJsonPath,
                    'Body' => $eventJson,
                    'ContentType' => 'application/json',
                    'ACL' => 'public-read'
                ]);
            }
            return true;
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                print_r('line no: ', $e->getLine());
                Log::error($e->getMessage());
            }
            print_r($e->getMessage());
            return null;
        }
    }
}