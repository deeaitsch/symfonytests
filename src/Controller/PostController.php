<?php

namespace App\Controller;

use App\Entity\Post;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PostController extends Controller
{


    /**
     * @Route("/post/add", name="post")
     */
    public function index()
    {

        $em = $this->getDoctrine()->getManager();
        $post = new Post();
        $post->setContent("Ich bin ein Post");
        $post->setDate(new \DateTime());
        $em->persist($post);
        $em->flush();


        return new Response(<<<HTML
        <html><body>{$post->getId()}</body></html>
HTML
);
    }

    /**
     * @Route("/post/{id}", name="show_post")
     */
    public function showPost($id) {
        $em = $this->getDoctrine()->getManager();
        $post = $em->getRepository(Post::class)->find($id);

        return new Response(<<<HTML
        <html><body>{$post->getContent()}</body></html>
HTML
        );
    }


}
