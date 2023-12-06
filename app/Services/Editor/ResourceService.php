<?php

namespace App\Services\Editor;

use App\Models\Event;
use App\Services\Event\EventService;
use App\Services\Uploads\S3Service;
use DOMDocument;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;
use Intervention\Image\Facades\Image;

class ResourceService 
{

    function getIncludedFilesContents($files, $dir){
        $newObj["files"] = json_decode($files, true);
        //exit;
        // Get all files
        // $basePath = public_path('plugin/editor/projects/template/');

        foreach ($newObj["files"] as $key => $value) {
            try{
                /*
                if (strpos($key, "novi.css") !== false) {
                    $s3Client = (new S3Service)->S3ClientObject();
                    $bucketName = env('AWS_BUCKET');
                    $defaultS3Folder = env('AWS_DEFAULT_FOLDER');
                    $pathTouseInKey = substr($dir, strpos($dir, $defaultS3Folder));
                    $noviCSSKey = $pathTouseInKey.$key;
                    if($s3Client->doesObjectExist($bucketName, $noviCSSKey)) {
                        $content = file_get_contents($dir.$key);
                        $newObj['files'][$key] = ($content && !empty($content)) ? $content : "";    
                    }
                }
                if(file_get_contents($dir.$key)){
                }
                */
                $content = file_get_contents($dir.$key);
                $newObj['files'][$key] = $content;
            } catch (Exception $e) {
                $newObj['files'][$key] = "";
            }
        }
        return json_encode($newObj);
    }

    public function transliterateString($txt) {
        $transliterationTable = array('á' => 'a', 'Á' => 'A', 'à' => 'a', 'À' => 'A', 'ă' => 'a', 'Ă' => 'A', 'â' => 'a', 'Â' => 'A', 'å' => 'a', 'Å' => 'A', 'ã' => 'a', 'Ã' => 'A', 'ą' => 'a', 'Ą' => 'A', 'ā' => 'a', 'Ā' => 'A', 'ä' => 'ae', 'Ä' => 'AE', 'æ' => 'ae', 'Æ' => 'AE', 'ḃ' => 'b', 'Ḃ' => 'B', 'ć' => 'c', 'Ć' => 'C', 'ĉ' => 'c', 'Ĉ' => 'C', 'č' => 'c', 'Č' => 'C', 'ċ' => 'c', 'Ċ' => 'C', 'ç' => 'c', 'Ç' => 'C', 'ď' => 'd', 'Ď' => 'D', 'ḋ' => 'd', 'Ḋ' => 'D', 'đ' => 'd', 'Đ' => 'D', 'ð' => 'dh', 'Ð' => 'Dh', 'é' => 'e', 'É' => 'E', 'è' => 'e', 'È' => 'E', 'ĕ' => 'e', 'Ĕ' => 'E', 'ê' => 'e', 'Ê' => 'E', 'ě' => 'e', 'Ě' => 'E', 'ë' => 'e', 'Ë' => 'E', 'ė' => 'e', 'Ė' => 'E', 'ę' => 'e', 'Ę' => 'E', 'ē' => 'e', 'Ē' => 'E', 'ḟ' => 'f', 'Ḟ' => 'F', 'ƒ' => 'f', 'Ƒ' => 'F', 'ğ' => 'g', 'Ğ' => 'G', 'ĝ' => 'g', 'Ĝ' => 'G', 'ġ' => 'g', 'Ġ' => 'G', 'ģ' => 'g', 'Ģ' => 'G', 'ĥ' => 'h', 'Ĥ' => 'H', 'ħ' => 'h', 'Ħ' => 'H', 'í' => 'i', 'Í' => 'I', 'ì' => 'i', 'Ì' => 'I', 'î' => 'i', 'Î' => 'I', 'ï' => 'i', 'Ï' => 'I', 'ĩ' => 'i', 'Ĩ' => 'I', 'į' => 'i', 'Į' => 'I', 'ī' => 'i', 'Ī' => 'I', 'ĵ' => 'j', 'Ĵ' => 'J', 'ķ' => 'k', 'Ķ' => 'K', 'ĺ' => 'l', 'Ĺ' => 'L', 'ľ' => 'l', 'Ľ' => 'L', 'ļ' => 'l', 'Ļ' => 'L', 'ł' => 'l', 'Ł' => 'L', 'ṁ' => 'm', 'Ṁ' => 'M', 'ń' => 'n', 'Ń' => 'N', 'ň' => 'n', 'Ň' => 'N', 'ñ' => 'n', 'Ñ' => 'N', 'ņ' => 'n', 'Ņ' => 'N', 'ó' => 'o', 'Ó' => 'O', 'ò' => 'o', 'Ò' => 'O', 'ô' => 'o', 'Ô' => 'O', 'ő' => 'o', 'Ő' => 'O', 'õ' => 'o', 'Õ' => 'O', 'ø' => 'oe', 'Ø' => 'OE', 'ō' => 'o', 'Ō' => 'O', 'ơ' => 'o', 'Ơ' => 'O', 'ö' => 'oe', 'Ö' => 'OE', 'ṗ' => 'p', 'Ṗ' => 'P', 'ŕ' => 'r', 'Ŕ' => 'R', 'ř' => 'r', 'Ř' => 'R', 'ŗ' => 'r', 'Ŗ' => 'R', 'ś' => 's', 'Ś' => 'S', 'ŝ' => 's', 'Ŝ' => 'S', 'š' => 's', 'Š' => 'S', 'ṡ' => 's', 'Ṡ' => 'S', 'ş' => 's', 'Ş' => 'S', 'ș' => 's', 'Ș' => 'S', 'ß' => 'SS', 'ť' => 't', 'Ť' => 'T', 'ṫ' => 't', 'Ṫ' => 'T', 'ţ' => 't', 'Ţ' => 'T', 'ț' => 't', 'Ț' => 'T', 'ŧ' => 't', 'Ŧ' => 'T', 'ú' => 'u', 'Ú' => 'U', 'ù' => 'u', 'Ù' => 'U', 'ŭ' => 'u', 'Ŭ' => 'U', 'û' => 'u', 'Û' => 'U', 'ů' => 'u', 'Ů' => 'U', 'ű' => 'u', 'Ű' => 'U', 'ũ' => 'u', 'Ũ' => 'U', 'ų' => 'u', 'Ų' => 'U', 'ū' => 'u', 'Ū' => 'U', 'ư' => 'u', 'Ư' => 'U', 'ü' => 'ue', 'Ü' => 'UE', 'ẃ' => 'w', 'Ẃ' => 'W', 'ẁ' => 'w', 'Ẁ' => 'W', 'ŵ' => 'w', 'Ŵ' => 'W', 'ẅ' => 'w', 'Ẅ' => 'W', 'ý' => 'y', 'Ý' => 'Y', 'ỳ' => 'y', 'Ỳ' => 'Y', 'ŷ' => 'y', 'Ŷ' => 'Y', 'ÿ' => 'y', 'Ÿ' => 'Y', 'ź' => 'z', 'Ź' => 'Z', 'ž' => 'z', 'Ž' => 'Z', 'ż' => 'z', 'Ż' => 'Z', 'þ' => 'th', 'Þ' => 'Th', 'µ' => 'u', 'а' => 'a', 'А' => 'a', 'б' => 'b', 'Б' => 'b', 'в' => 'v', 'В' => 'v', 'г' => 'g', 'Г' => 'g', 'д' => 'd', 'Д' => 'd', 'е' => 'e', 'Е' => 'E', 'ё' => 'e', 'Ё' => 'E', 'ж' => 'zh', 'Ж' => 'zh', 'з' => 'z', 'З' => 'z', 'и' => 'i', 'И' => 'i', 'й' => 'j', 'Й' => 'j', 'к' => 'k', 'К' => 'k', 'л' => 'l', 'Л' => 'l', 'м' => 'm', 'М' => 'm', 'н' => 'n', 'Н' => 'n', 'о' => 'o', 'О' => 'o', 'п' => 'p', 'П' => 'p', 'р' => 'r', 'Р' => 'r', 'с' => 's', 'С' => 's', 'т' => 't', 'Т' => 't', 'у' => 'u', 'У' => 'u', 'ф' => 'f', 'Ф' => 'f', 'х' => 'h', 'Х' => 'h', 'ц' => 'c', 'Ц' => 'c', 'ч' => 'ch', 'Ч' => 'ch', 'ш' => 'sh', 'Ш' => 'sh', 'щ' => 'sch', 'Щ' => 'sch', 'ъ' => '', 'Ъ' => '', 'ы' => 'y', 'Ы' => 'y', 'ь' => '', 'Ь' => '', 'э' => 'e', 'Э' => 'e', 'ю' => 'ju', 'Ю' => 'ju', 'я' => 'ja', 'Я' => 'ja');
        return str_replace(array_keys($transliterationTable), array_values($transliterationTable), $txt);
    }

    public function saveProject($project, $companySlug, $eventSlug) 
    {
        // $htmlContent = '<html><body><h1>Hello, Screenshot!</h1></body></html>';

        // $screenshot = Browsershot::url("https://strm-operations.s3.ap-southeast-1.amazonaws.com/mayur/editor/template/option_1/pages/index.html")
        // ->setNodeBinary('C:/Program Files/nodejs/node.exe')->screenshot();
        // dd($screenshot);
        $event = Event::where('slug', $eventSlug)->first();
        $templateSlug = $event->template_option_slug;
        if(!$templateSlug) {
            exit();
        }
        $json_project = base64_decode($project);
        $projectObj = json_decode($json_project, true);
        // dd($projectObj["pages"]);
        if (isset($projectObj) && $projectObj != "null") {
            $dir = $projectObj["dir"];
            $bucketName = env('AWS_BUCKET');
            $defaultS3Folder = env('AWS_DEFAULT_FOLDER');
            $pathTouseInKey = substr($dir, strpos($dir, $defaultS3Folder));

            // $dir = resource_path("views/event/$companySlug/$eventSlug");
            $eventBladeFolderPath = '/resources/views/event/' . $companySlug . '/' . $eventSlug;

            if (!Storage::disk('disk_path')->exists($eventBladeFolderPath)) {
                Storage::disk('disk_path')->makeDirectory($eventBladeFolderPath, 0775, true); //creates directory
            }

            $s3Client = (new S3Service)->S3ClientObject();
            if (isset($projectObj["pages"])) {
                // dd($projectObj['pages']);
                for ($i = 0; $i < count($projectObj["pages"]); $i++) {
                    if (isset($projectObj["pages"][$i]["html"])) {
                        $fileName = basename($projectObj["pages"][$i]["path"], '.html').".blade.php";
                        if(isset($projectObj["pages"][$i]["index"]) && $projectObj["pages"][$i]["index"] == TRUE) {
                            (new EventService)->updateEvent(['home_page' => $fileName], $event->id);
                        }
                        // echo $fileName."\n";

                        $htmlContent = preg_replace_callback('/data-constraints=("|\')(.*?)\1/', function($matches) {
                            $attributes = $matches[2];
                            $replacedAttributes = str_replace('@', '@@', $attributes);
                            return 'data-constraints=' . $matches[1] . $replacedAttributes . $matches[1];
                        }, $projectObj["pages"][$i]["html"]);

                        $newBaseUrl = env('AWS_USE_PUBLIC_DEFAULT_URL')."$companySlug/$eventSlug/$templateSlug/";

                        $sourceFilePathIndexJS = public_path('js/event/default/index.js');
                        $destinationDirectoryIndexJS = public_path("js/event/$companySlug/$eventSlug");
                        $destinationFilePathIndexJS = $destinationDirectoryIndexJS . '/index.js';

                        // Check if the source file exists
                        if (File::exists($sourceFilePathIndexJS)) {
                            // Ensure the destination directory exists, and create it if necessary
                            if (!File::isDirectory($destinationDirectoryIndexJS)) {
                                File::makeDirectory($destinationDirectoryIndexJS, 0755, true, true);
                            }
                            File::put($destinationFilePathIndexJS, File::get($sourceFilePathIndexJS));

                            // if (File::exists($destinationFilePathIndexJS)) {
                            //     // Append the content of the source file to the destination file
                            //     file_put_contents($destinationFilePathIndexJS, file_get_contents($sourceFilePathIndexJS), FILE_APPEND);
                            // } else {
                                // If the destination file doesn't exist, copy the source file to the destination
                                File::copy($sourceFilePathIndexJS, $destinationFilePathIndexJS);
                            // }
                        }

                        //$pattern = '/(src|href)=["\']((?!https:)[^"\']*)["\']/i';
                        //$pattern = '/(src|href|data-parallax-img)=["\']((?!https:)[^"\']*)["\']/i';
                        $pattern = '/(src|href|data-parallax-img|data-slide-bg|data-src)=["\']((?!https:)[^"\']*)["\']/i';
                        // $pattern = '/(src|href|data-parallax-img|data-slide-bg|data-src)=["\']((?!https:)[^"\']*)["\'](?!.*data-bs-toggle="tab"|.*\bclass="[^"]*nav-link[^"]*")/i';

                        // $modifiedHtml = preg_replace($pattern, "$1=\"$newBaseUrl$2\"", $htmlContent);
                        $modifiedHtml = preg_replace_callback($pattern, function ($matches) use ($newBaseUrl) {
                            $attribute = $matches[1];
                            $originalValue = $matches[2];
                        
                            // Check if the originalValue starts with 'https://' or '#'
                            if (strpos($originalValue, 'https://') === 0 || strpos($originalValue, '#') === 0) {
                                // If it already starts with 'https://' or '#', don't modify it
                                return "$attribute=\"$originalValue\"";
                            } else {
                                // If it doesn't start with 'https://' or '#', prepend $newBaseUrl
                                return "$attribute=\"$newBaseUrl$originalValue\"";
                            }
                        }, $htmlContent);

                        // $modifiedHtml = preg_replace_callback('/<a\s(.*?)class="(.*?nav-link active.*?)["\'](.*?)<\/a>/s', function ($matches) {
                        //     $beforeAttrs = $matches[1];
                        //     $navLinkClass = $matches[2];
                        //     $afterAttrs = $matches[3];
                        //     dd($beforeAttrs, $afterAttrs, $navLinkClass);
                        
                        //     // Remove the "active" class while preserving other classes in the "class" attribute
                        //     $navLinkClass = str_replace('active', '', $navLinkClass);
                        
                        //     // Reconstruct the <a> tag
                        //     return "<a $beforeAttrs class=\"$navLinkClass\"$afterAttrs</a>";
                        // }, $modifiedHtml);
                        // dd($modifiedHtml);

                        $indexScript = "
                            <script>
                                var companySlug = \"$companySlug\";
                                var eventSlug = \"$eventSlug\";
                            </script>
                            
                            <script src=\"https://code.jquery.com/ui/1.13.2/jquery-ui.js\"></script>
                            <script src=\"https://fengyuanchen.github.io/cropperjs/js/cropper.js\"></script>
                            <script src=\"{{ asset(\"js/event/$companySlug/$eventSlug/index.js\") }}\"></script>
                            <script src=\"{{ asset(\"assets/libs/sweetalert2/sweetalert2.all.min.js\") }}\"></script>
                            <script src=\"{{ asset(\"assets/libs/parsleyjs/parsley.min.js\") }} \"></script>
                            <script src=\"{{ asset(\"js/custom/commonParsley.js\") }}\"></script>
                        ";
                        $modifiedHtmlWithScript = $modifiedHtml;
                        $scriptSrcPattern = "/js\/event\/$companySlug\/$eventSlug\/index\.js/";

                        // Check if the script source pattern is found in the HTML content
                        if (!preg_match($scriptSrcPattern, $htmlContent)) {
                            if (strpos($modifiedHtmlWithScript, '<head>') !== false) {
                                $headLinks = "
                                    <link rel=\"stylesheet\" href=\"{{ asset(\"assets/css/bootstrap.min.css\") }} \">
                                    <script src=\"{{ asset(\"assets/libs/bootstrap/js/bootstrap.min.js\") }} \"></script>
                                    <script src=\"https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js\"></script>
                                    <link href=\"{{ asset(\"assets/libs/select2/css/select2.min.css\") }} \" rel=\"stylesheet\" type=\"text/css\" />
                                    <script src=\"{{ asset(\"assets/libs/select2/js/select2.min.js\") }} \"></script>
                                    <link href=\"https://parsleyjs.org/src/parsley.css\" rel=\"stylesheet\" type=\"text/css\" />
                                    <link href=\"{{ asset('assets/libs/sweetalert2/sweetalert2.min.css') }}\" rel=\"stylesheet\" type=\"text/css\" />
                                    
                                    <style>
                                        .select2-results {
                                            max-height: 200px;
                                            overflow: auto;
                                        }
                                        select.select2-hidden-accessible {
                                            -webkit-appearance: none !important;
                                            -moz-appearance: none !important;
                                            appearance: none !important;
                                            background: transparent !important;
                                            padding: 0 !important;
                                        }
                                        .select2-selection.select2-selection--single {
                                            height: fit-content !important;
                                        }
                                    </style>
                                ";
                                $modifiedHtmlWithScript = str_replace('<head>', '<head>' . $headLinks, $modifiedHtmlWithScript);
                            }
                            if (strpos($modifiedHtmlWithScript, '</body>') !== false) {
                                // Define the script you want to append
                            
                                // Search for the closing </body> tag and replace it with the script + </body>
                                $modifiedHtmlWithScript = str_replace('</body>', $indexScript . '</body>', $modifiedHtmlWithScript);
                            
                                // Now, save the modified HTML with the appended script
                                Storage::disk('disk_path')->put($eventBladeFolderPath . '/' . $fileName, $modifiedHtmlWithScript);
                            } elseif(strpos($modifiedHtmlWithScript, '</html>') !== false) {
                                $modifiedHtmlWithScript = str_replace('</html>', $indexScript . '</html>', $modifiedHtmlWithScript);
                            } else {
                                $modifiedHtmlWithScript = $modifiedHtmlWithScript . $indexScript;
                            }
                        }
                        
                        Storage::disk('disk_path')->put($eventBladeFolderPath . '/' . $fileName, $modifiedHtmlWithScript);

                        $key = $pathTouseInKey.$projectObj["pages"][$i]["path"];
                        // dd($projectObj["pages"][$i]["html"]);
                        $s3Client->putObject([
                            'Bucket' => $bucketName,
                            'Key' => $key,
                            'Body' => $projectObj["pages"][$i]["html"],
                            'ContentType' => 'text/html',
                            'ACL' => 'public-read'
                        ]);
                        $s3Client->putObject([
                            'Bucket' => $bucketName,
                            'Key' => $pathTouseInKey."/".basename($projectObj['pages'][$i]['path']),
                            'Body' => $projectObj["pages"][$i]["html"],
                            'ContentType' => 'text/html',
                            'ACL' => 'public-read'
                        ]);
                        $projectObj["pages"][$i]["preview"] = "";
                        unset($projectObj["pages"][$i]["html"]);
                    }
                    // echo json_encode($projectObj);
                }
                // dd();
            }

            
            if(isset($projectObj["presets"])) {
                $presetsDir   = $pathTouseInKey . "elements/";
                $newBaseUrl = env('AWS_USE_PUBLIC_DEFAULT_URL')."$companySlug/$eventSlug/$templateSlug/";

                //fetch from s3 bucket
                (new S3Service)->checkForAWSObject($bucketName, $presetsDir);

                $result = $s3Client->listObjects([
                    'Bucket' => $bucketName,
                    'Prefix' => $pathTouseInKey."elements/"
                ]);

                $presetsFiles = [];
                if ($result && isset($result['Contents']) && count($result['Contents'])) {
                    foreach ($result['Contents'] as $object) {
                        if (!$object['Key'] === $presetsDir) {
                            $presetsFiles[] = basename($object['Key']);
                        }
                    }
                }

                $newFiles     = array();
                // for ($i = 0; $i < count($projectObj["presets"]); $i++) {
                //     if (!isset($projectObj["presets"][$i]["html"])) {
                //         array_push($newFiles, $projectObj["presets"][$i]["path"]);
                //     }
                // }
                for ($i = 0; $i < count($projectObj["presets"]); $i++) {
                    if (isset($projectObj["presets"][$i]["html"])) {
                        $title       = preg_replace("/\s+/", "-", strtolower(preg_replace('/[\?|\||\\|\/|\:|\*|\>|\<|\.|\"|\,]/', "", $projectObj["presets"][$i]["title"])));
                        $title = $this->transliterateString($title);
                        $newFileName = $title . ".html";
                        $j           = 0;
                        if (in_array($newFileName, $newFiles)) {
                            $j = 1;
                            while (in_array($title . "-" . $j . ".html", $newFiles)) {
                                $j++;
                            }
                            $newFileName = $title . "-" . $j . ".html";
                        }
                        array_push($newFiles, $newFileName);
                        array_push($presetsFiles, $newFileName);
                        $projectObj["presets"][$i]["path"] = $newFileName;
                        $s3Key                          = $presetsDir . $newFileName;
                        $fileName                          = $newFileName;
                        // if (($key = array_search($fileName, $presetsFiles)) !== false) {
                            // unset($presetsFiles[$key]);
                        // }
                        $categories = explode("-", File::name($newFileName));
                        $projectObj["presets"][$i]["categories"] = $categories;
                        $s3Client->putObject([
                            'Bucket' => $bucketName,
                            'Key' => $s3Key,
                            'Body' => $projectObj["presets"][$i]["html"],
                            'ContentType' => 'text/html',
                            'ACL' => 'public-read'
                        ]);

                        if (!empty($projectObj["presets"][$i]["preview"])){
                            // $ext     = pathinfo($dir . $projectObj["presets"][$i]["preview"]);
                            // $preview = basename($dir . $projectObj["presets"][$i]["preview"], "." . $ext['extension']);
                            // if (($j == 0 && $preview != $title) || ($j > 0 && $preview != $title . "-" . $j)) {
                            //     $ext = "." . $ext["extension"];
                            //     if ($j > 0) {
                            //         $newPreviewName = $title . "-" . $j;
                            //     } else {
                            //         $newPreviewName = $title;
                            //     }
                                // if (file_exists($presetsDir . "/" . $newPreviewName . $ext)) {
                                //     $k = 1;
                                //     while (file_exists($presetsDir . "/" . $newPreviewName . "-" . $k . $ext)) {
                                //         $k++;
                                //     }
                                //     $newPreviewName = $newPreviewName . "-" . $k;
                                // }
                                // rename($dir . $projectObj["presets"][$i]["preview"], $presetsDir . "/" . $newPreviewName . $ext);
                                $newPreviewName = uniqid("preview_img") . ".jpg";
                                $s3Key = $presetsDir . $newPreviewName;
                                $imageFolderPath = $presetsDir . "images/";
                                // $elementFolderPath = $presetsDir ."elements/";
                                // $screenshot = Browsershot::html($htmlContent)->screenshot();
                                // dd($screenshot);
                                $previewimage = Image::make($newBaseUrl.$projectObj["presets"][$i]["preview"]);
                                $type = $previewimage->mime();
                                $previewimage = $previewimage->stream()->detach();
                                $s3Client->putObject([
                                    'Bucket' => $bucketName,
                                    'Key' => $s3Key,
                                    'Body' =>  $previewimage,
                                    'ACL' => 'public-read',
                                    'ContentType' => $type
                                ]);
                                $s3Client->putObject([
                                    'Bucket' => $bucketName,
                                    'Key' => $imageFolderPath,
                                    'Body' => $previewimage,
                                    'ACL' => 'public-read',
                                    'ContentType' => $type
                                ]);
                                // $previewimage->destroy();
                                $projectObj["presets"][$i]["preview"] = "elements/" . $newPreviewName;
                        }
                        $projectObj["presets"][$i]["preview"] = "elements/page-manager-fallback.svg";

                        unset($projectObj["presets"][$i]["html"]);
                    }
                }
            }

            if (isset($projectObj["files"])) {

                $contentTypeMapping = [
                    'txt' => 'text/plain',
                    'html' => 'text/html',
                    'htm' => 'text/html',
                    'xml' => 'application/xml',
                    'json' => 'application/json',
                    'pdf' => 'application/pdf',
                    'doc' => 'application/msword',
                    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'xls' => 'application/vnd.ms-excel',
                    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'csv' => 'text/csv',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'bmp' => 'image/bmp',
                    'svg' => 'image/svg+xml',
                    'mp4' => 'video/mp4',
                    'mp3' => 'audio/mpeg',
                    'wav' => 'audio/wav',
                    'css' => 'text/css',
                    'js' => 'text/javascript',
                    'zip' => 'application/zip',
                    'tar' => 'application/x-tar',
                    'gz' => 'application/gzip',
                    'php' => 'application/x-httpd-php',
                    'py' => 'text/x-python',
                    'rb' => 'application/x-ruby',
                    'java' => 'text/x-java-source',
                    // Add more as needed
                ];

                foreach ($projectObj["files"] as $filePath => $fileContent) {

                    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                    // Check if the file extension exists in the mapping
                    if (array_key_exists($extension, $contentTypeMapping)) {
                        $contentType = $contentTypeMapping[$extension];
                    } else {
                        // Handle unknown file extensions here
                        $contentType = 'application/octet-stream'; // Default to a binary type
                    }

                    $key = $pathTouseInKey.$filePath;
                    $s3Client->putObject([
                        'Bucket' => $bucketName,
                        'Key' => $key,
                        'Body' => $fileContent['content'],
                        'ACL' => 'public-read',
                        'ContentType' => $contentType
                    ]);
                    //unset($projectObj["files"][$filePath]);
                }
            }

            $file       = $pathTouseInKey . "project.json";
            $projectStr = json_encode($projectObj);

            $s3Client->putObject([
                'Bucket' => $bucketName,
                'Key' => $file,
                'Body' => $projectStr,
                'ContentType' => 'application/json',
                'ACL' => 'public-read'
            ]);
    
            echo $projectStr;
        }
            

            
                // $presetsFiles = scandir($presetsDir);
                /*
                foreach ($presetsFiles as $key => $value) {
                    if (preg_match("/[^\.]\..*$/", $value)) {
                        if (preg_match('/\.html$/', $value)) {
                            if (!in_array($value, $newFiles)) {
                                $preview = $presetsDir . "/" . basename($value, ".html");
                                if (file_exists($preview . ".jpg")) {
                                    unlink($preview . ".jpg");
                                } else if (file_exists($preview . ".png")) {
                                    unlink($preview . ".png");
                                }
                                unlink($presetsDir . "/" . $value);
                            }
                        } else {
                            $presetFile = basename($value);
                            $removeFile = true;
                            for ($i = 0; $i < count($projectObj["presets"]); $i++) {
                                $presetPreview = basename($projectObj["presets"][$i]["preview"]);
                                if ($presetPreview == $presetFile) {
                                    $removeFile = false;
                                    break;
                                }
                            }
                            if ($removeFile) {
                                if (file_exists($presetsDir . "/" . $value)) {
                                    unlink($presetsDir . "/" . $value);
                                }
                            }
                        }
                    }
                }
                */
    }
}