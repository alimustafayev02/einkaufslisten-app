<?php

namespace App\Controller;

use App\Repository\ShoppingListRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Frontend Controller - rendert die HTML-Oberfläche.
 * Die Oberfläche kommuniziert via JavaScript mit der REST-API.
 */
class FrontendController extends AbstractController
{
    public function __construct(
        private readonly ShoppingListRepository $listRepo,
    ) {
    }

    /**
     * Startseite: Übersicht aller Einkaufslisten.
     */
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(): Response
    {
        $lists = $this->listRepo->findBy([], ['createdAt' => 'DESC']);
        return $this->render('lists/index.html.twig', [
            'lists' => $lists,
        ]);
    }

    /**
     * Detailansicht einer Einkaufsliste.
     */
    #[Route('/lists/{id}', name: 'list_detail', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function detail(int $id): Response
    {
        $list = $this->listRepo->find($id);
        if (!$list) {
            throw $this->createNotFoundException('Einkaufsliste nicht gefunden.');
        }
        return $this->render('lists/detail.html.twig', [
            'list' => $list,
        ]);
    }
}
