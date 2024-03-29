<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;
use function PHPUnit\Framework\fileExists;

include public_path() . '/simple_html_dom.php';

use function simplehtmldom_1_5\file_get_html;

class WebpageController extends Controller
{
    public function downloadPage($change_link, $url)
    {
        $context = stream_context_create(array(
            'http' => array(
                'header' => array('User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.1; rv:2.2) Gecko/20110201'),
            ),
        ));

        //Генерируем название страницы, и читаем нужную страницу
        $page_name = Str::random(20);
        $webpage = file_get_html($url, 0, $context);

        if ($webpage && mkdir('./pages/' . $page_name, 0777, true)) {
            $path = './pages/' . $page_name;
            mkdir($path . '/img', 0777, true);
            mkdir($path . '/css', 0777, true);
            mkdir($path . '/js', 0777, true);

            //Достаем название директивы(если сайт установлен на трекере, то ссылка будет другая и настоящая директива будет хранится здесь)
            $base_elem = $webpage->find('head base[href]', 0);

            // --- IMAGES ---
            foreach ($webpage->find('img') as $image) {
                try {
                    //Получаем полную ссылку на изображение
                    $image_format = $this->formatLink($url, $image->src, $base_elem);

                    $image_name = pathinfo($image_format)['basename'];

                    //Сохраняем локально изображение, и меняем путь к нему в верстке, на наш локальный
                    copy($image_format, $path . '/img/' . $image_name);
                    $image->src = './img/' . $image_name;
                    $image->srcset = '';
                } catch (Exception $ex) {
                }
            }

            //Скачивает background фотки, которые установлены в стили блоков
            foreach($webpage->find('div[style]') as $div){
                preg_match('/url\((.*)\)/', $div->style, $match);
                if (isset($match[1])){ //Если есть url(ссыкла на фотку)
                    $image_link = trim($match[1], '\'" ');

                    //Получаем полную ссылку на изображение
                    $image_format = $this->formatLink($url, $image_link, $base_elem);
                    
                    $image_name = pathinfo($image_format)['basename'];

                    //Сохраняем локально изображение, и меняем путь к нему в верстке, на наш локальный
                    copy($image_format, $path . '/img/' . $image_name, $context);
                    $div->style = str_replace($image_link, './img/' . $image_name, $div->style);
                }
            }


            // --- STYLES ---
            foreach ($webpage->find('link[rel="stylesheet"]') as $stylesheet) {
                try {
                    //Получаем полную ссылку на стили
                    $style = $this->formatLink($url, $stylesheet->href, $base_elem);

                    $style_name = pathinfo($style)['basename'];

                    //Сохраняем локально файл стилей, и меняем к нему путь в верстке, на наш локальный
                    copy($style, $path . '/css/' . $style_name, $context);
                    $stylesheet->href = './css/' . str_replace('?', '%3F', $style_name);
                } catch (Exception $ex) {
                }
            }

            // --- SCRIPTS ---
            foreach ($webpage->find('script') as $script) {
                try {
                    if (isset($script->src) && $script->src && !str_contains($script->src, 'http')) {//Получаем только те скрипты, которые хранятся на сервере
                        //Получаем полную ссылку на скрипт
                        $script_format = $this->formatLink($url, $script->src, $base_elem);
                        $script_name = pathinfo($script_format)['basename'];

                        //Сохраняем локально файл скрипта, и меняем к нему путь в верстке, на наш локальный
                        copy($script_format, $path . '/js/' . $script_name, $context);
                        $script->src = './js/' . $script_name;
                    } elseif (!$script->src) {//Если не указан путь к скрипту (то есть скрипт написан на самой странице) и в нем есть метрики яндекса либо гугла - то удаляем скрипт
                        if (str_contains($script->innertext, 'google-analytics') || str_contains($script->innertext, 'metrika.yandex') || str_contains($script->innertext, 'yandex_metrika') || str_contains($script->innertext, 'mc.yandex.ru')) {
                            $script->innertext = '';
                        }
                    }
                } catch (Exception $ex) {
                }
            }

            //Заменяем ссылки на переданую пользователем ссылку
            foreach ($webpage->find('a') as $link) {
                if (str_contains($link->href, 'http')) {
                    $link->href = $change_link;
                }
            }

            //Если указана базовая директива, удаляем, так как у нас все в корне хранится
            if(isset($base_elem->href)) {
                $base_elem->href = '';
            }

            //Устанавливаем, чтобы нормально выводился язык
            $meta_lang = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
            $webpage->find('head', 0)->innertext .= $meta_lang;

            $webpage->save();

            file_put_contents($path . '/index.html', $webpage);

            //ARCHIVE TO ZIP AND DOWNLOAD
            $zip = new ZipArchive();
            $filename = "./pages/".$page_name.".zip";

            if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
                echo "Cannot open ".$filename."<br>";
            }else{
                $dir = $path.'/';

                // Create zip
                $this->createZip($zip,$dir);
                $zip->close();
                //Download zip, and delete it on server
                //Storage::deleteDirectory($path);
                $this->downloadZip($filename);
            }


            return $page_name;
        } else {
            return view('alert', ['type' => 'error', 'page_name' => '']);
        }
    }

    //Получение ссылки на которой хранится файл
    public function formatLink($url, $link, $base = '')
    {
        //Получаем корень ссылки - (https://www.some_site.com)
        $url_check = pathinfo($url)['dirname'];
        if($url_check != 'https:' && $url_check != 'http:'){
            $url = $url_check;
        }

        //Проверяем есть ли директива(если это сайт с трекера)
        if(isset($base->href)) {
            $base = $base->href;
        }else {
            $base = '';
        }

        //Если файл находится на сервере
        if(!str_contains($link, 'http')){
            //Если указана полная ссылка на фото, но без https/http - (//check/img.jpg)
            if($link[0] == '/' && $link[1] == '/'){
                $link = 'https:'.$link;
                if($this->checkFileExisting($link) === false){
                    return str_replace('https', 'http', $link);
                }
                else{
                    return $link;
                }
            }

            //Если файл находится на сервере, то добавляем к нему ссылку на страницу, чтобы был полный путь
            if (fileExists($url . "/" . $link)) {

                if($link[0] == '/') {//Если файл находится в корне сайта
                    $parsed_url = parse_url($url);
                    $link_check = $parsed_url['scheme'].'://'.$parsed_url['host'].$link;
                }else{
                    $link_check = $url . "/" . $link;
                }

                //Если такого файла по ссылке нету, но у нас есть директива - то скорей всего он будет там
                if ($base && $this->checkFileExisting($link_check) === false) {
                    try {
                        $base = pathinfo($base)['dirname'];
                        $link = $url . $base . "/" . $link;
                    }catch (Exception $ex){
                        $link = $link_check;
                    }
                }else{
                    $link = $link_check;
                }
            }
        }

        return $link;
    }

    //Проверка существования файла
    public function checkFileExisting($file_link)
    {
        try {
            if (false !== file_get_contents($file_link, 0, null, 0, 1)) {//файл существует
                return true;
            }
        } catch (Exception $ex) {//файла не существует
        }

        return false;
    }

    public function downloadZip($zip_name){
        if (file_exists($zip_name)) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="'.basename($zip_name).'"');
            header('Content-Length: ' . filesize($zip_name));
            header("Pragma: no-cache");
            header("Expires: 0");

            //flush();
            readfile($zip_name);
            // delete file
            unlink($zip_name);
        }
    }

    public function createZip($zip, $dir){
        if (is_dir($dir) && $dh = opendir($dir)){
            while (($file = readdir($dh)) !== false){
                // If file
                if (is_file($dir.$file)) {
                    if($file != '' && $file != '.' && $file != '..'){
                        $zip->addFile($dir.$file);
                    }
                }else{// If directory
                    if(is_dir($dir.$file) ){
                        if($file != '' && $file != '.' && $file != '..'){
                            // Add empty directory
                            $zip->addEmptyDir($dir.$file);

                            $folder = $dir.$file.'/';

                            // Read data of the folder
                            $this->createZip($zip, $folder);
                        }
                    }
                }
            }
            closedir($dh);
        }
    }

    public function copyWebpage(Request $request)
    {
        if ($request->get('site_link') && $request->get('change_link')) {
            $url = $request->get('site_link');
            $change_link = $request->get('change_link');

            $page_name = $this->downloadPage($change_link, $url);
            return view('alert', ['type' => 'success', 'page_name' => $page_name]);
        }
    }
}
