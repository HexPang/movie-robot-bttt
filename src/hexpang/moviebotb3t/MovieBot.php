<?php
/**\
 * @description Movie Bot For bttiantang.com
 * @author hexpang
 */

namespace hexpang\moviebotb3t;
require_once "vendor/simple-html-dom/simple-html-dom/simple_html_dom.php";

class MovieBot{
  var $baseUrl;
  public function __construct(){
    $this->baseUrl = 'http://www.bttiantang.com/movie.php?/order/update/{page}/';
  }
  function loadUrl($url,$cache=false){
    $cacheFile = "cache/" . urlencode($url) . '.cache';
    if(file_exists($cacheFile) && $cache){
      $url = $cacheFile;
    }
    $response = null;
    $response = @file_get_contents($url);
    if($cache){
      $handle = fopen($cacheFile,"w+");
      fwrite($handle,$response);
      fflush($handle);
      fclose($handle);
    }
    return $response;
  }

  function loadWithPage($page = 1){
    $URL = str_ireplace('{page}',$page,$this->baseUrl);
    $response = $this->loadUrl($URL);
    return $response;
  }
  function loadTorrentInfo($url){
    $url = "http://www.bttiantang.com" . $url;
    $src = $this->loadUrl($url);
    if($src == null){
      return null;
    }
    $html = str_get_html($src);
    $torrents = $html->find("div[class=tinfo]");
    $result = [];
    foreach ($torrents as $torrent) {
      $file = $torrent->find("p[class=torrent]")[0];
      $link = $torrent->find("a")[0];
      $result[] = ['file_name'=>$file->plaintext,'url'=>$link->href];
    }
    return $result;
  }
  function loadMovies($page){
    $pQuery = new \simple_html_dom();
    $source = $this->loadWithPage($page);
    $html = str_get_html($source);
    $total_page = $html->find("ul[class=pagelist] span")[0];
    $pages = explode("/",$total_page->innertext);
    $total_page = mb_substr($pages[0],1,mb_strlen($pages[0])-2);
    $total_result = mb_substr($pages[1],0,mb_strlen($pages[1])-1);
    $movies = $html->find("div[class=perone]");
    if(!$movies || count($movies) == 0){
      return false;
    }
    $r_movies = [];
    foreach($movies as $movie){
      $title = $movie->find("div[class=minfo] h2 a")[0];
      $href = $movie->find("h2 a")[0];
      $types = $movie->find("ul li")[0];
      $types = $types->find("a");
      $countries = $movie->find("ul li")[1];
      $countries = $countries->find("a");
      $director = $movie->find("ul li")[2];
      $director = $director->find("a");
      $script = $movie->find("ul li")[3];
      $script = $script->find("a");
      $stars = $movie->find("ul li")[4];
      $stars = $stars->find("a");
      $movie_script = [];
      $movie_director = [];
      $movie_type = [];
      $movie_countries = [];
      $movie_actor = [];
      $image = $movie->find("div[class=litpic] a img")[0];
      foreach($stars as $s){
        $movie_actor[] = $s->innertext;
      }
      foreach($director as $actor){
        $movie_director[] = $actor->innertext;
      }
      foreach($script as $s){
        $movie_script[] = $s->innertext;
      }
      foreach($countries as $country){
        $movie_countries[] = $country->innertext;
      }
      foreach($types as $type){
        $movie_type[] = $type->innertext;
      }
      $movie = [
        'title'=>$title->plaintext,
        'url'=>$href->href,
        'type'=>$movie_type,
        'country'=>$movie_countries,
        'director'=>$movie_director,
        'script'=>$movie_script,
        'actor'=>$movie_actor,
        'image'=>$image->src
      ];
      $torrents = $this->loadTorrentInfo($href->href);
      $r_movies[] = $movie;
    }

    return ['movies'=>$r_movies,'total_result'=>$total_result,'total_page'=>$total_page];
  }
}
?>
