<?php
/**\
 * @description Movie Bot For bttiantang.com
 * @author hexpang
 */

namespace hexpang\moviebotb3t;

if (file_exists('vendor/simple-html-dom/simple-html-dom/simple_html_dom.php')) {
    require_once 'vendor/simple-html-dom/simple-html-dom/simple_html_dom.php';
} else {
    require_once __DIR__.'/../../../../../simple-html-dom/simple-html-dom/simple_html_dom.php';
}

class MovieBot
{
    public $baseUrl;
    public function __construct()
    {
        $this->baseUrl = 'http://www.bttiantang.com/movie.php?/type,order/{type},update/{page}/';
        // $this->baseUrl = 'http://www.bttiantang.com/movie.php?/type,order/update/{page}/';
    }
    public function downloadTorrent($url, $fileName = null)
    {
        $url = 'http://www.bttiantang.com'.$url;
        $source = $this->loadUrl($url);
        $html = str_get_html($source);
        $input = $html->find('input[type=hidden]');
        $data = [];
        foreach ($input as $v) {
            $data[$v->name] = $v->value;
        }

        $url = 'http://www.bttiantang.com/download1.php';
        $post_data = $data;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);
        //打印获得的数据
        if ($fileName == null) {
            return $output;
        }
        $handle = fopen($fileName, 'w+');
        fwrite($handle, $output);
        fflush($handle);
        fclose($handle);

        return true;
    }
    public function loadUrl($url, $cache = false)
    {
        $cacheFile = 'cache/'.urlencode($url).'.cache';
        if (file_exists($cacheFile) && $cache) {
            $url = $cacheFile;
        }
        $response = null;
        $response = file_get_contents($url);
        if ($cache) {
            $handle = fopen($cacheFile, 'w+');
            fwrite($handle, $response);
            fflush($handle);
            fclose($handle);
        }

        return $response;
    }

    public function loadWithPage($page = 1, $type = '0')
    {
        $URL = str_ireplace('{page}', $page, $this->baseUrl);
        $URL = str_ireplace('{type}', $type, $this->baseUrl);
        $response = $this->loadUrl($URL);

        return $response;
    }
    public function loadTorrentInfo($url)
    {
        $url = 'http://www.bttiantang.com'.$url;
        $src = $this->loadUrl($url);

        if ($src == null) {
            return;
        }

        $html = str_get_html($src);
        $torrents = $html->find('div[class=tinfo]');
        $result = [];
        foreach ($torrents as $torrent) {
            $tree = [];
            $tree_html = $torrent->find('span[class=video]');

            foreach ($tree_html as $t) {
                $tree[] = $t->innertext;
            }

            $file = $torrent->find('p[class=torrent]')[0];
            $link = $torrent->find('a')[0];
            $result[] = ['file_name' => $file->plaintext, 'url' => $link->href, 'files' => $tree];
        }

        return $result;
    }
    public function loadMovieInfo($id)
    {
        $url = "http://www.bttiantang.com/subject/{$id}.html";
        $src = $this->loadUrl($url);
        if ($src == null) {
            return;
        }
        $html = str_get_html($src);

        $title_html = $html->find('div[class=title] h2')[0];
        $title = $title_html->plaintext;
        //.'/'.$title_html->find('span')[0]->innertext;
        if ($title_html->find('span')) {
            $title .= '/'.$title_html->find('span')[0]->innertext;
        }
        $info_block = $html->find('ul[class=moviedteail_list]')[0];

        $field = ['type', 'country', 'year', 'director', 'script', 'actor'];
        $info_html = $info_block->find('li');

        $info = [];
        foreach ($field as $i => $f) {
            $hh = $info_html[$i]->find('a');
            $a = [];
            foreach ($hh as $h) {
                $a[] = $h->innertext;
            }
            $info[$f] = $a;
        }
        $image_html = $html->find('div[class=moviedteail_img]')[0];
        $image = $image_html->find('img')[0]->src;
        $score_html = $html->find('p[class=rt]')[0];
        $score = $score_html->find('strong')[0]->innertext;
        if (count($score_html->find('em[class=dian]')) > 0) {
            $f = $score_html->find('em[class=fm]')[0];
            $score .= '.'.$f->innertext;
        }
        $info['title'] = $title;
        $info['url'] = "/subject/{$id}.html";
        $info['id'] = $id;
        $info['image'] = $image;
        $info['score'] = $score;

        return $info;
    }
    public function loadMovies($page, $type = '0')
    {
        $pQuery = new \simple_html_dom();
        $source = $this->loadWithPage($page, $type);
        $html = str_get_html($source);

        $total_page = $html->find('ul[class=pagelist] span')[0];
        $pages = explode('/', $total_page->innertext);
        $total_page = mb_substr($pages[0], 1, mb_strlen($pages[0]) - 2);
        $total_result = mb_substr($pages[1], 0, mb_strlen($pages[1]) - 1);
        $movies = $html->find('div[class=perone]');
        if (!$movies || count($movies) == 0) {
            return false;
        }
        $r_movies = [];
        $pattern = '/\$(\d+\.?\d+)/';
        foreach ($movies as $movie) {
            $title = $movie->find('div[class=minfo] h2 a')[0];
            $score = $movie->find('strong[class=sum]')[0];
            $score = $score->innertext;
            $href = $movie->find('h2 a')[0];
            $types = $movie->find('ul li')[0];
            $types = $types->find('a');
            $countries = $movie->find('ul li')[1];
            $countries = $countries->find('a');
            $director = $movie->find('ul li')[2];
            $director = $director->find('a');
            $script = $movie->find('ul li')[3];
            $script = $script->find('a');
            $stars = $movie->find('ul li')[4];
            $stars = $stars->find('a');
            $movie_script = [];
            $movie_director = [];
            $movie_type = [];
            $movie_countries = [];
            $movie_actor = [];
            $image = $movie->find('div[class=litpic] a img')[0];
            foreach ($stars as $s) {
                $movie_actor[] = $s->innertext;
            }
            foreach ($director as $actor) {
                $movie_director[] = $actor->innertext;
            }
            foreach ($script as $s) {
                $movie_script[] = $s->innertext;
            }
            foreach ($countries as $country) {
                $movie_countries[] = $country->innertext;
            }
            foreach ($types as $type) {
                $movie_type[] = $type->innertext;
            }
            $id = explode('/', $href->href);
            $id = $id[count($id) - 1];
            $id = explode('.', $id);
            $id = $id[0];
            $movie = [
        'title' => $title->plaintext,
        'url' => $href->href,
        'type' => $movie_type,
        'country' => $movie_countries,
        'director' => $movie_director,
        'script' => $movie_script,
        'actor' => $movie_actor,
        'image' => $image->src,
        'id' => $id,
        'score' => $score,
      ];
            // $torrents = $this->loadTorrentInfo($href->href);
            $r_movies[] = $movie;
        }

        return ['movies' => $r_movies, 'total_result' => $total_result, 'total_page' => $total_page];
    }
}
