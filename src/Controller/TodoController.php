<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Todo;
use App\Form\NewCategoryType;
use App\Form\TodoType;
use App\Repository\TodoRepository;
use App\Form\Type\TaskType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use PhpParser\Node\Stmt\Label;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
* @isGranted("IS_AUTHENTICATED_REMEMBERED")
*/
class TodoController extends AbstractController
{

    /**
     * @Route("/todos/{id?}", methods={"GET", "POST"}, name="todos")
     * 
     */
    public function todos(EntityManagerInterface $entityManager, Request $request, ?int $id): Response
    {
        
        // Checking rights to acces category from $id
        if ($id) {
            $categoriesCheck = $entityManager->getRepository(Category::class)->find($id);
            if (!$categoriesCheck) {
                throw $this->createNotFoundException('No category found');
            }
            if ($categoriesCheck->getOwner() !== $this->getUser()) {
                throw $this->createNotFoundException('No acces to this category');
            }
        }

        // Rendering todos
        $todos = $entityManager->getRepository(Todo::class)
        ->findBy([
            'owner' => $this->getUser(),
            'category' => $id,
        ]);
        // Rendering categories
        $categories = $entityManager->getRepository(Category::class)
        ->findBy(
            ['owner' => $this->getUser()]
        );
        
        // Creating new Category
        $category = new Category();

        $category->setOwner($this->getUser());
        $categoryForm = $this->createForm(NewCategoryType::class, $category);
        $categoryForm->handleRequest($request);
        if ($categoryForm->isSubmitted() && $categoryForm->isValid())
        {
            $entityManager->persist($category);
            $entityManager->flush();

            return $this->redirect($request->headers->get('referer'));
        }

        // Creating new TODO
        $todo = new Todo();
        
        $todo
            ->setOwner($this->getUser())
            ->setCreationDate(new \DateTime());
        $form = $this->createForm(TodoType::class, $todo, [
            'userId' => $this->getUser(),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager->persist($todo);
            $entityManager->flush();
            
            return $this->redirect($request->headers->get('referer'));
        } 


        return $this->render('todo/todos.html.twig', [
            'todos' => $todos,
            'categories' => $categories,
            'form' => $form->createView(),
            'categoryForm' => $categoryForm->createView(),
        ]);
    }

   /**
    * @Route("/remove/{id}", methods="GET", name="remove")
    */
   public function removeTodo(EntityManagerInterface $entityManager, int $id, Request $request)
   {
        $todo = $entityManager->getRepository(Todo::class)->find($id);

        if (!$todo) {
            throw $this->createNotFoundException('No todo found');
        }

        if ($todo->getOwner() === $this->getUser())
        {
            $entityManager->remove($todo);
            $entityManager->flush();
        } else {
            throw $this->createNotFoundException('No access to this todo');
        }


       return $this->redirect($request->headers->get('referer'));
   }

   /**
    * @Route("/edit/{id}", name="edit")
    */
   public function editTodo(EntityManagerInterface $entityManager, int $id, Request $request)
   {

        $todo = $entityManager->getRepository(Todo::class)->find($id);

        if (!$todo) {
            throw $this->createNotFoundException('No todo found');
        }

        if ($todo->getOwner() === $this->getUser())
        {
            if ($todo->getIsDone()) {
                $todo->setIsDone(false);
            } else {
                $todo->setIsDone(true);
            }
            $entityManager->flush();
        } else {
            throw $this->createNotFoundException('No access to this todo');
        }

        return $this->redirect($request->headers->get('referer'));
   }

   /**
    * @Route("category/remove/{id}", methods="GET", name="remove_category")
    */
    public function removeCategory(EntityManagerInterface $entityManager, int $id, Request $request)
    {
         $category = $entityManager->getRepository(Category::class)->find($id);
 
         if (!$category) {
             throw $this->createNotFoundException('No category found');
         }
 
         if ($category->getOwner() === $this->getUser())
         {
             $entityManager->remove($category);
             $entityManager->flush();
         } else {
             throw $this->createNotFoundException('No access to this category');
         }
 
 
        return $this->redirectToRoute('todos');
    }

}
