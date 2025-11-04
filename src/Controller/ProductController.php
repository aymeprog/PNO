<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/products')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'products_index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $repo): Response
    {
        // Get the search query from the GET parameter
        $query = $request->query->get('q', '');

        if (!empty($query)) {
            // Search products by name (case-insensitive)
            $products = $repo->createQueryBuilder('p')
                ->where('LOWER(p.name) LIKE LOWER(:search)')
                ->setParameter('search', '%' . $query . '%')
                ->orderBy('p.id', 'DESC')
                ->getQuery()
                ->getResult();
        } else {
            // Default: show all products
            $products = $repo->findBy([], ['id' => 'DESC']);
        }

        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/new', name: 'product_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle uploaded image file
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/images',
                    $newFilename
                );
                $product->setImage($newFilename);
            }

            $product->setCreatedAt(new \DateTimeImmutable());
            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Product created.');
            return $this->redirectToRoute('products_index');
        }

        return $this->render('product/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', compact('product'));
    }

    #[Route('/{id}/edit', name: 'product_edit', methods: ['GET','POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle new uploaded image if available
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/images',
                    $newFilename
                );
                $product->setImage($newFilename);
            }

            $em->flush();
            $this->addFlash('success', 'Product updated.');
            return $this->redirectToRoute('products_index');
        }

        return $this->render('product/edit.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }

    #[Route('/{id}/delete', name: 'product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            // Delete product image from folder if exists
            if ($product->getImage()) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public/images/' . $product->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $em->remove($product);
            $em->flush();
            $this->addFlash('success', 'Product removed.');
        }

        return $this->redirectToRoute('products_index');
    }
}
