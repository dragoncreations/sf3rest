<?php

namespace Dragoncreations\RestBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Request\ParamFetcher;
use Symfony\Component\Validator\ConstraintViolationList;
use Dragoncreations\RestBundle\Entity\Person;

class ApiController extends FOSRestController {

    /**
     * Return the overall person list.
     * [GET] /api/people
     *
     * @return View
     */
    public function getPeopleAction() {
        $em = $this->container->get('doctrine.orm.entity_manager');

        $entity = $em->getRepository('DragoncreationsRestBundle:Person')->findAll();

        if (!$entity) {
            throw $this->createNotFoundException('Data not found.');
        }

        $view = View::create();
        $view->setData($entity)->setStatusCode(200);

        return $view;
    }

    /**
     * Return person by ID.
     * [GET] /api/people/{id}
     * 
     * @param int $id Person ID
     * 
     * @return View
     */
    public function getPersonAction($id) {
        $em = $this->container->get('doctrine.orm.entity_manager');

        $entity = $em->getRepository('DragoncreationsRestBundle:Person')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Data not found.');
        }

        $view = View::create();
        $view->setData($entity)->setStatusCode(200);

        return $view;
    }

    /**
     * Create Person from submitted data.<br/>
     * [POST] /api/people
     * 
     * @param ParamFetcher $paramFetcher Paramfetcher
     * 
     * @RequestParam(name="firstname", nullable=false, strict=true, description="Firstname.")
     * @RequestParam(name="lastname", nullable=false, strict=true, description="Lastname.")
     * 
     * @return View
     */
    public function postPersonAction(ParamFetcher $paramFetcher) {
        $person = new Person();
        $person->setFirstname($paramFetcher->get('firstname'));
        $person->setLastname($paramFetcher->get('lastname'));

        $view = View::create();

        $errors = $this->get('validator')->validate($person);

        if (count($errors) == 0) {
            $em = $this->container->get('doctrine.orm.entity_manager');
            $em->persist($person);
            $em->flush();
        } else {
            $view = $this->getErrorsView($errors);
            return $view;
        }

        $view->setData($person)->setStatusCode(200);
        return $view;
    }

    /**
     * Update a Person from the submitted data by ID.<br/>
     * [PUT] /api/person
     *
     * @param ParamFetcher $paramFetcher Paramfetcher
     * 
     * @RequestParam(name="id", nullable=false, strict=true, description="id.")
     * @RequestParam(name="firstname", nullable=true, strict=true, description="Firstame.")
     * @RequestParam(name="lastname", nullable=true, strict=true, description="Lastname.")
     * 
     * @return View
     */
    public function putPersonAction(ParamFetcher $paramFetcher) {
        $em = $this->container->get('doctrine.orm.entity_manager');

        $person = $em->getRepository('DragoncreationsRestBundle:Person')->findOneBy(
                array('id' => $paramFetcher->get('id'))
        );

        if ($paramFetcher->get('firstname')) {
            $person->setFirstname($paramFetcher->get('firstname'));
        }

        if ($paramFetcher->get('lastname')) {
            $person->setLastname($paramFetcher->get('lastname'));
        }

        $view = View::create();
        $errors = $this->get('validator')->validate($person);

        if (count($errors) == 0) {
            $em->persist($person);
            $em->flush();
            $view->setData($person)->setStatusCode(200);
            return $view;
        } else {
            $view = $this->getErrorsView($errors);
            return $view;
        }
    }

    /**
     * Delete a person identified by ID.
     * [DELETE] /api/people/{id}
     * 
     * @param int $id Person ID
     * 
     * @return View
     */
    public function deletePersonAction($id) {
        $em = $this->container->get('doctrine.orm.entity_manager');

        $person = $em->getRepository('DragoncreationsRestBundle:Person')->findOneBy(
                array('id' => $id)
        );

        if (!$person) {
            throw $this->createNotFoundException('Data not found.');
        }

        $em->remove($person);
        $em->flush();

        $view = View::create();
        $view->setData("Person deleted.")->setStatusCode(204);
        return $view;
    }

    /**
     * Get the validation errors
     *
     * @param ConstraintViolationList $errors Validator error list
     *
     * @return View
     */
    protected function getErrorsView(ConstraintViolationList $errors) {
        $msgs = array();
        $errorIterator = $errors->getIterator();
        foreach ($errorIterator as $validationError) {
            $msg = $validationError->getMessage();
            $params = $validationError->getMessageParameters();
            $msgs[$validationError->getPropertyPath()][] = $this->get('translator')->trans($msg, $params, 'validators');
        }
        $view = View::create($msgs);
        $view->setStatusCode(400);
        return $view;
    }

}
