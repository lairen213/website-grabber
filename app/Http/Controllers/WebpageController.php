<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use function PHPUnit\Framework\fileExists;

include public_path().'/simple_html_dom.php';
use function simplehtmldom_1_5\file_get_html;

class WebpageController extends Controller
{
    public function downloadPage($change_link, $url)
    {
        //Генерируем название страницы, и читаем нужную страницу
        $page_name = Str::random(20);
        $webpage = file_get_html($url);

        if ($webpage && mkdir('./pages/' . $page_name, 0777, true)) {
            $path = './pages/' . $page_name;
            mkdir($path . '/img', 0777, true);
            mkdir($path . '/css', 0777, true);
            mkdir($path . '/js', 0777, true);

            // --- IMAGES ---
            foreach ($webpage->find('img') as $image) {
                try {
                    //Получаем полную ссылку на изображение
                    $image_format = $this->formatLink($url, $image->src);

                    $image_name = pathinfo($image_format)['basename'];

                    //Сохраняем локально изображение, и меняем путь к нему в верстке, на наш локальный
                    copy($image_format, $path . '/img/' . $image_name);
                    $image->src = './img/' . $image_name;
                } catch (Exception $ex) {
                }
            }


            // --- STYLES ---
            foreach ($webpage->find('link[rel="stylesheet"]') as $stylesheet) {
                try {
                    //Получаем полную ссылку на стили
                    $style = $this->formatLink($url, $stylesheet->href);

                    $style_name = pathinfo($style)['basename'];

                    //Сохраняем локально файл стилей, и меняем к нему путь в верстке, на наш локальный
                    copy($style, $path . '/css/' . $style_name);
                    $stylesheet->href = './css/' . str_replace('?', '%3F', $style_name);
                } catch (Exception $ex) {
                }
            }

            // --- SCRIPTS ---
            foreach ($webpage->find('script') as $script) {
                try {
                    if (isset($script->src) && $script->src && !str_contains($script->src, 'http')) {//Получаем только те скрипты, которые хранятся на сервере
                        //Получаем полную ссылку на скрипт
                        $script_format = $this->formatLink($url, $script->src);
                        $script_name = pathinfo($script_format)['basename'];

                        //Сохраняем локально файл скрипта, и меняем к нему путь в верстке, на наш локальный
                        copy($script_format, $path . '/js/' . $script_name);
                        $script->src = './js/' . $script_name;
                    } elseif (!$script->src) {//Если не указан путь к скрипту (то есть скрипт написан на самой странице) и в нем есть метрики яндекса либо гугла - то удаляем скрипт
                        if (str_contains($script->innertext, 'google-analytics') || str_contains($script->innertext, 'metrika.yandex') || str_contains($script->innertext, 'yandex_metrika') || str_contains($script->innertext, 'mc.yandex.ru')) {
                            $script->innertext = '';
                        }
                    }
                } catch (Exception $ex) {
                }
            }

            //Заменяем врнутренние ссылки на переданую пользователем ссылку
            foreach ($webpage->find('a') as $link) {
                if (!str_contains($link->href, 'http')) {
                    $link->href = $change_link;
                }
            }

            $webpage->save();

            file_put_contents($path . '/index.html', $webpage);
            return $page_name;
        }
    }

    public function formatLink($url, $link)
    {
        //Если файл находится не на cdn, а на сервере, то добавляем к нему ссылку на страницу, чтобы был полный путь
        if (!str_contains($link, 'http') && fileExists(pathinfo($url)['dirname'] . "/" . $link)) {
            $link = pathinfo($url)['dirname'] . "/" . $link;
        }
        return $link;
    }

    public function copyWebpage(Request $request)
    {
        if ($request->get('site_link') && $request->get('change_link')) {
            $url = $request->get('site_link');
            $change_link = $request->get('change_link');

            $page_name = $this->downloadPage($change_link, $url);
            return view('success', ['page_name' => $page_name]);
        }
    }
}
