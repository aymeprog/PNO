<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    #[Route('/cart', name: 'cart_index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $repo): Response
    {
        $cart = $request->getSession()->get('cart', []);
        // $cart = [ productId => qty ]
        $items = [];
        $total = 0;
        foreach ($cart as $id => $qty) {
            $p = $repo->find($id);
            if (!$p) continue;
            $items[] = [
                'product' => $p,
                'qty' => $qty,
                'subtotal' => $p->getPrice() * $qty
            ];
            $total += $p->getPrice() * $qty;
        }

        return $this->render('cart/index.html.twig', compact('items','total'));
    }

    #[Route('/cart/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function add(int $id, Request $request, ProductRepository $repo): Response
    {
        $product = $repo->find($id);
        if (!$product) {
            $this->addFlash('danger', 'Product not found.');
            return $this->redirectToRoute('products_index');
        }

        $qty = max(1, (int)$request->request->get('qty', 1));
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        $cart[$id] = ($cart[$id] ?? 0) + $qty;
        $session->set('cart', $cart);

        $this->addFlash('success', 'Added to cart.');
        return $this->redirectToRoute('cart_index');
    }

    #[Route('/cart/update/{id}', name: 'cart_update', methods: ['POST'])]
    public function update(int $id, Request $request, ProductRepository $repo): Response
    {
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        $qty = max(0, (int)$request->request->get('qty', 1));

        if ($qty <= 0) {
            unset($cart[$id]);
        } else {
            $cart[$id] = $qty;
        }

        $session->set('cart', $cart);
        $this->addFlash('success', 'Cart updated.');
        return $this->redirectToRoute('cart_index');
    }

    #[Route('/cart/remove/{id}', name: 'cart_remove', methods: ['POST'])]
    public function remove(int $id, Request $request): Response
    {
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        unset($cart[$id]);
        $session->set('cart', $cart);
        $this->addFlash('success', 'Item removed from cart.');
        return $this->redirectToRoute('cart_index');
    }

    #[Route('/cart/clear', name: 'cart_clear', methods: ['POST'])]
    public function clear(Request $request): Response
    {
        $request->getSession()->remove('cart');
        $this->addFlash('success', 'Cart cleared.');
        return $this->redirectToRoute('products_index');
    }
}
