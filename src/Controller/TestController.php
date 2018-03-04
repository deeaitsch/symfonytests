<?php

namespace App\Controller;

use App\Entity\TestEntity;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TestController extends Controller
{

    const RABBITMQ_Q_NAME = "testQ";
    /**
     * @Route("/test", name="test")
     */
    public function index()
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/TestController.php',
        ]);
    }

    /**
     * @Route("/testentity/insert/{amount}", defaults={"amount"=100})
     * @param integer $amount Amount of inserted TestEnitiy Objects
     * @return Response
     */
    public function insertObjects($amount) {
        $entityManager = $this->getDoctrine()->getManager();
        for($i = 0; $i < $amount; $i++) {
            $entity = new TestEntity();
            $entity->setName("Entität {$i}");
            $entityManager->persist($entity);
        }
        $entityManager->flush();

        return new Response(<<<TAG
{$amount} neue Einträge gemacht
TAG
);
    }
    /**
     * @Route("/testentity/read")
     * @return Response
     */
    public function readObjects() {
        $entityManager = $this->getDoctrine()->getManager();
        $allEntities = $entityManager->getRepository(TestEntity::class)->findAll();
        $amount = count($allEntities);
        return new Response(<<<TAG
{$amount} Einträge vorhanden
TAG
        );
    }

    /**
     * @Route("/testentity/rabbit/produce/{id}")
     * @param $id
     */
    public function produce($id) {
        $entityManager = $this->getDoctrine()->getManager();
        $entity = $entityManager->getRepository(TestEntity::class)->find($id);
        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();
        $channel->queue_declare(self::RABBITMQ_Q_NAME, false, false, false, false);
        $message = new AMQPMessage(serialize($entity));
        $channel->basic_publish($message, '', self::RABBITMQ_Q_NAME);
        $channel->close();
        $connection->close();
        return new Response(<<<TAG
{$entity->getId()} in die Warteschlange eingefügt
TAG
        );
    }

    /**
     * @Route("/testentity/rabbit/consume")
     */
    public function consume() {
        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();
        $channel->queue_declare(self::RABBITMQ_Q_NAME, false, false, false, false);
        $callback = function($message) {
            var_dump(unserialize($message->getBody()));
        };

        $channel->basic_consume(self::RABBITMQ_Q_NAME, '', false, true, false, true, $callback);

        $amount = 0;
        while(count($channel->callbacks)) {
            $channel->wait();
            $amount++;
        }

        $channel->close();
        $connection->close();

        return new Response(<<<TAG
{$amount} Entitäten aus der Warteschlange gelesen
TAG
        );
    }
}
