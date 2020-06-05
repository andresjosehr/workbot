<?php

namespace App\Http\Controllers;
use GuzzleHttp\Client;

use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Proyect;

class TasksController extends Controller
{

    public function getWorkanaInfo(\Goutte\Client $client)
    {

        $workanaPage = $client->request('GET', 'https://www.workana.com/jobs?category=it-programming&language=es');

        $results = [];
        
        $results =  $workanaPage->filter('#projects .project-item')->each(function (Crawler $node, $i) {

                        $data = [];
                        $data["title"]             = $node->filter('.project-title span')->extract(['title'])[0];
                        $data["published"]         = $node->filter('.date')->text();
                try {   $data["deadline"]          = $node->filter('.deadline .value')->text(); } catch (\Throwable $th) { $data["deadline"] = null; }
                        $data["bids"]              = $node->filter('.bids')->text();
                        $data["price"]             = $node->filter('.values')->text();
                        $data["country"]           = $node->filter('.country-name')->text();
                        $data["link"]              = "https://workana.com".$node->filter('.project-title a')->extract(['href'])[0];
                        $data["description"]       = $node->filter('.expander')->text();

                        return $data;

                    });

        $newProyects = self::compareData($results);
        
        if($newProyects){
            foreach($newProyects as $newProyect) 
                self::sendMessage($client, $newProyect);
        }

        Proyect::truncate();
        Proyect::insert($results);

        return (new Response("Success", 200));
    }


    public function compareData($data){

        $newProyect=true;
        $newProyectData = [];
        
        foreach ($data as $key => $value) {
            $newProyect=true;

            foreach (Proyect::all()->toArray() as $key2 => $value2) {
                if($value["title"]==$value2["title"]){
                    $newProyect=false;
                }

            }
            if($newProyect && ($value["published"]=="Hace instantes" || $value["published"]=="Just now")){
                $newProyectData[$key] = $value;
            }
        }

        return $newProyectData;

    }


    public function sendMessage($client, $data){

        // <b>bold</b>, <strong>bold</strong>
        // <i>italic</i>, <em>italic</em>
        // <u>underline</u>, <ins>underline</ins>
        // <s>strikethrough</s>, <strike>strikethrough</strike>, <del>strikethrough</del>
        // <b>bold <i>italic bold <s>italic bold strikethrough</s> <u>underline italic bold</u></i> bold</b>
        // <a href="http://www.example.com/">inline URL</a>
        // <a href="tg://user?id=123456789">inline mention of a user</a>
        // <code>inline fixed-width code</code>
        // <pre>pre-formatted fixed-width code block</pre>
        // <pre><code class="language-python">pre-formatted fixed-width code block written in the Python programming language</code></pre>


        $html = '<b>!NUEVO PROYECTO PUBLICADO! </b>'.chr(10).'
                <pre></pre>'.chr(10).'
                <b>Titulo: </b> Titulo'.chr(10).'
                <b>Publicado: </b> Publicado'.chr(10).'
                <b>Plazo: </b> Plazo'.chr(10).'
                <b>Presupuesto: </b> Presupuesto'.chr(10).'
                <b>Pais: </b> Pais'.chr(10).'
                <b>link: </b> link'.chr(10).'
                <b>Descripcion: </b> Titulo Descripcion';


                $message = <<<TEXT

                    ------- <b>¡NUEVO PROYECTO PUBLICADO!</b> -------

                    <b>Titulo:</b> $data[title];

                    <b>Publicado:</b> $data[published];

                    <b>Plazo:</b> $data[deadline];

                    <b>Propuestas:</b> $data[bids];

                    <b>Presupuesto:</b> $data[price];

                    <b>Pais:</b> $data[country];

                    <b>link:</b> $data[link];

                    <b>Descripcion:</b> $data[description];

                    ---------------------------------------------------------------
                    TEXT;
                    $message = urlencode($message);
  
        try {
            $client->request('GET', 'https://api.telegram.org/bot1299600512:AAEGCf2ufchepoeUSwFFO-CDdRpTnUx9IKE/sendMessage?chat_id=641746913&parse_mode=HTML&text='.$message);
        } catch (\Throwable $th) {
            return $th;
        }

        return "ok";
    }
}
