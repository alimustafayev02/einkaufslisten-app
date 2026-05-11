<?php

namespace App\Controller;

use App\Entity\ShoppingList;
use App\Entity\ShoppingListItem;
use App\Repository\ShoppingListItemRepository;
use App\Repository\ShoppingListRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * REST API Controller für die Einkaufslisten-App.
 *
 * Stellt alle in der Aufgabenstellung geforderten Endpunkte bereit:
 *   POST   /api/lists                       Erstellt eine Einkaufsliste mit X Einträgen
 *   POST   /api/lists/{id}/item             Erstellt einen neuen Eintrag
 *   GET    /api/lists/{id}/items            Gibt die ganze Liste zurück
 *   GET    /api/lists/{id}/items/{itemId}   Gibt ein einzelnes Item zurück
 *   PUT    /api/lists/{id}/items/{itemId}   Aktualisiert ein Item
 *   DELETE /api/lists/{id}                  Löscht eine Liste
 *   DELETE /api/lists/{id}/items/{itemId}   Löscht ein Item
 */
#[Route('/api', name: 'api_')]
class ShoppingListApiController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ShoppingListRepository $listRepo,
        private readonly ShoppingListItemRepository $itemRepo,
    ) {
    }

    /**
     * 1. POST /api/lists
     * Erstellt eine Einkaufsliste mit X Einträgen.
     *
     * Body (JSON):
     * {
     *   "name": "Wocheneinkauf",
     *   "items": [
     *     { "name": "Milch", "quantity": 2 },
     *     { "name": "Brot", "quantity": 1 }
     *   ]
     * }
     */
    #[Route('/lists', name: 'create_list', methods: ['POST'])]
    public function createList(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->error('Ungültiges JSON.', Response::HTTP_BAD_REQUEST);
        }

        $name = trim((string)($data['name'] ?? ''));
        if ($name === '') {
            return $this->error('Das Feld "name" ist erforderlich.', Response::HTTP_BAD_REQUEST);
        }

        $list = new ShoppingList($name);

        // Optionale Einträge direkt mit anlegen
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $itemData) {
                $itemName = trim((string)($itemData['name'] ?? ''));
                if ($itemName === '') {
                    continue;
                }
                $quantity = max(1, (int)($itemData['quantity'] ?? 1));
                $item = new ShoppingListItem($itemName, $quantity);
                $list->addItem($item);
            }
        }

        $this->em->persist($list);
        $this->em->flush();

        return new JsonResponse($list->toArray(), Response::HTTP_CREATED);
    }

    /**
     * 2. POST /api/lists/{id}/item
     * Erstellt einen neuen Eintrag in der Einkaufsliste.
     * Antwort: die aktualisierte Einkaufsliste.
     */
    #[Route('/lists/{id}/item', name: 'create_item', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function createItem(int $id, Request $request): JsonResponse
    {
        $list = $this->listRepo->find($id);
        if (!$list) {
            return $this->error('Einkaufsliste nicht gefunden.', Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->error('Ungültiges JSON.', Response::HTTP_BAD_REQUEST);
        }

        $name = trim((string)($data['name'] ?? ''));
        if ($name === '') {
            return $this->error('Das Feld "name" ist erforderlich.', Response::HTTP_BAD_REQUEST);
        }
        $quantity = max(1, (int)($data['quantity'] ?? 1));

        $item = new ShoppingListItem($name, $quantity);
        $list->addItem($item);

        $this->em->persist($item);
        $this->em->flush();

        return new JsonResponse($list->toArray(), Response::HTTP_CREATED);
    }

    /**
     * 3. GET /api/lists/{id}/items
     * Gibt die ganze Einkaufsliste zurück.
     */
    #[Route('/lists/{id}/items', name: 'get_items', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getItems(int $id): JsonResponse
    {
        $list = $this->listRepo->find($id);
        if (!$list) {
            return $this->error('Einkaufsliste nicht gefunden.', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($list->toArray(), Response::HTTP_OK);
    }

    /**
     * 4. GET /api/lists/{id}/items/{itemId}
     * Gibt das übergebene Item der Einkaufsliste zurück.
     */
    #[Route('/lists/{id}/items/{itemId}', name: 'get_item', methods: ['GET'], requirements: ['id' => '\d+', 'itemId' => '\d+'])]
    public function getItem(int $id, int $itemId): JsonResponse
    {
        $item = $this->findItemInList($id, $itemId);
        if ($item instanceof JsonResponse) {
            return $item;
        }

        return new JsonResponse($item->toArray(), Response::HTTP_OK);
    }

    /**
     * 5. PUT /api/lists/{id}/items/{itemId}
     * Aktualisiert das Item.
     *
     * Body (JSON): { "name": "...", "quantity": 3, "checked": true }
     * Alle Felder sind optional, nur übergebene Felder werden aktualisiert.
     */
    #[Route('/lists/{id}/items/{itemId}', name: 'update_item', methods: ['PUT'], requirements: ['id' => '\d+', 'itemId' => '\d+'])]
    public function updateItem(int $id, int $itemId, Request $request): JsonResponse
    {
        $item = $this->findItemInList($id, $itemId);
        if ($item instanceof JsonResponse) {
            return $item;
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->error('Ungültiges JSON.', Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('name', $data)) {
            $newName = trim((string)$data['name']);
            if ($newName === '') {
                return $this->error('Der Name darf nicht leer sein.', Response::HTTP_BAD_REQUEST);
            }
            $item->setName($newName);
        }
        if (array_key_exists('quantity', $data)) {
            $item->setQuantity(max(1, (int)$data['quantity']));
        }
        if (array_key_exists('checked', $data)) {
            $item->setChecked((bool)$data['checked']);
        }

        $this->em->flush();

        return new JsonResponse($item->toArray(), Response::HTTP_OK);
    }

    /**
     * 6. DELETE /api/lists/{id}
     * Löscht eine Einkaufsliste (samt aller Einträge).
     */
    #[Route('/lists/{id}', name: 'delete_list', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteList(int $id): JsonResponse
    {
        $list = $this->listRepo->find($id);
        if (!$list) {
            return $this->error('Einkaufsliste nicht gefunden.', Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($list);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * 7. DELETE /api/lists/{id}/items/{itemId}
     * Löscht ein Item aus der Einkaufsliste.
     */
    #[Route('/lists/{id}/items/{itemId}', name: 'delete_item', methods: ['DELETE'], requirements: ['id' => '\d+', 'itemId' => '\d+'])]
    public function deleteItem(int $id, int $itemId): JsonResponse
    {
        $item = $this->findItemInList($id, $itemId);
        if ($item instanceof JsonResponse) {
            return $item;
        }

        $this->em->remove($item);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Zusatz-Endpoint: GET /api/lists
     * Listet alle Einkaufslisten auf (nützlich für die Übersicht im Frontend).
     */
    #[Route('/lists', name: 'get_lists', methods: ['GET'])]
    public function getLists(): JsonResponse
    {
        $lists = $this->listRepo->findBy([], ['createdAt' => 'DESC']);
        return new JsonResponse(
            array_map(fn(ShoppingList $l) => $l->toArray(), $lists),
            Response::HTTP_OK
        );
    }

    // ---------- Helper-Methoden ----------

    /**
     * Sucht ein Item innerhalb einer Liste.
     * Gibt entweder das Item oder eine Fehler-JsonResponse zurück.
     */
    private function findItemInList(int $listId, int $itemId): ShoppingListItem|JsonResponse
    {
        $list = $this->listRepo->find($listId);
        if (!$list) {
            return $this->error('Einkaufsliste nicht gefunden.', Response::HTTP_NOT_FOUND);
        }
        $item = $this->itemRepo->find($itemId);
        if (!$item || $item->getShoppingList()?->getId() !== $list->getId()) {
            return $this->error('Item nicht gefunden in dieser Liste.', Response::HTTP_NOT_FOUND);
        }
        return $item;
    }

    private function decodeJson(Request $request): ?array
    {
        $content = $request->getContent();
        if ($content === '') {
            return [];
        }
        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            return is_array($data) ? $data : null;
        } catch (\JsonException) {
            return null;
        }
    }

    private function error(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['error' => $message], $status);
    }
}
