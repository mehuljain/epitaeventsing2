<?php

namespace Events\Bundle\EventsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Events\Bundle\EventsBundle\Entity\User;
use Events\Bundle\EventsBundle\Form\Type\UserType;
use Symfony\Component\HttpFoundation\Request;
use Events\Bundle\EventsBundle\Entity\Subscribed;
use Events\Bundle\EventsBundle\Form\Type\EventoneType;
use Events\Bundle\EventsBundle\Form\Type\EventtwoType;
use Events\Bundle\EventsBundle\Form\Type\EventthreeType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DefaultController extends Controller {

    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction() {

//        return new RedirectResponse($this->generateUrl('closepage'));

        if ($this->get('security.context')->isGranted('ROLE_USER')) {
            return $this->redirect($this->generateUrl('securedhome'));
        }
        return array();
    }

    /**
     * @Route("/closepage",name="closepage")
     * @Template()
     */
    public function closeAction() {

        return array();
    }

    /**
     * @Route("/register",name="register")
     * @Template()
     */
    public function registerAction(Request $request) {

//        return new RedirectResponse($this->generateUrl('closepage'));

        $em = $this->getDoctrine()->getManager();
        //Check to see if the user has already logged in
        if ($this->get('security.context')->isGranted('ROLE_USER')) {
            return $this->redirect($this->generateUrl('securedhome'));
        }

        $user = new User();

        $form = $this->createForm(new UserType(), $user);
        $form->handleRequest($request);
        if ($form->isValid()) {
            //Do the needful
            $date = new \DateTime();
            $user->setCreatedon($date);
            $user->setEnabled(TRUE);
            $em->persist($user);
            $em->flush();
            $this->authenticateUser($user);
            $route = 'securedhome';
            $url = $this->generateUrl($route);
            return $this->redirect($url);
        }

        return array('form' => $form->createView());
    }

    /**
     * @Route("/secured/home",name="securedhome")
     * @Template()
     */
    public function homeAction(Request $request) {

//        return new RedirectResponse($this->generateUrl('closepage'));

        $em = $this->getDoctrine()->getManager();

        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return $this->redirect($this->generateUrl('events_events_default_index'));
        }
        $user = $em->getRepository('EventsEventsBundle:User')->find($this->get('security.context')->getToken()->getUser()->getId());

        if (!is_object($user) || !$user instanceof User) {
            throw new \Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException('This user does not have access to this section.');
        }

        return array();
    }

    /**
     * @Route("/secured/eventone",name="eventone")
     * @Template()
     */
    public function eventoneAction(Request $request) {

        $exists = false;

        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return $this->redirect($this->generateUrl('events_events_default_index'));
        }
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('EventsEventsBundle:User')->find($this->get('security.context')->getToken()->getUser()->getId());


        if (!is_object($user) || !$user instanceof User) {
            throw new \Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException('This user does not have access to this section.');
        }

        $subrecord = $em->getRepository('EventsEventsBundle:Subscribed')->findOneBy(array('user' => $user->getId()));

        if (!empty($subrecord)) {
            $exists = true;
            if ($subrecord->getEventtype1() != null || $subrecord->getEventtype1() != '') {
                $event1 = $subrecord->getEventtype1()->getId();
            } else {
                $event1 = '';
            }
            if (($subrecord->getEventtype2() != null || $subrecord->getEventtype2() != '')) {
                $event2 = $subrecord->getEventtype2()->getId();
            } else {
                $event2 = '';
            }
            if (($subrecord->getEventtype3() != null || $subrecord->getEventtype3() != '')) {
                $event3 = $subrecord->getEventtype3()->getId();
            } else {
                $event3 = '';
            }
        }

        $subscribed = new Subscribed();

        $form = $this->createForm(new EventoneType($subrecord), $subscribed);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            //First check the value entered by the user
            if ($subscribed->getEventtype1() == null ||
                    $subscribed->getEventtype2() == null ||
                    $subscribed->getEventtype3() == null
            ) {
                //User did not choose both the events
                $this->container->get('session')->getFlashBag()->add('error', 'Oh oh! It is mandatory to choose an option for all the events');
                return array('form' => $form->createView());
            }

            //Identical events should not be selected
            if (($subscribed->getEventtype2() == 3 && $subscribed->getEventtype3() == 10) ||
                    ($subscribed->getEventtype2() == 4 && $subscribed->getEventtype3() == 11) ||
                    ($subscribed->getEventtype2() == 5 && $subscribed->getEventtype3() == 12) ||
                    ($subscribed->getEventtype2() == 6 && $subscribed->getEventtype3() == 13) ||
                    ($subscribed->getEventtype2() == 7 && $subscribed->getEventtype3() == 14) ||
                    ($subscribed->getEventtype2() == 8 && $subscribed->getEventtype3() == 15) ||
                    ($subscribed->getEventtype2() == 9 && $subscribed->getEventtype3() == 16)
            ) {
                //User chose identical events
                $this->container->get('session')->getFlashBag()->add('error', 'Oh no! Not the same event twice. Please choose another event.');
                return array('form' => $form->createView());
            }


            $max = $this->container->getParameter('max_cultural');
            $maxfood = $this->container->getParameter('max_food');
            //Now check for the participants limit
            $qb1 = $em->createQueryBuilder();
            $qb1->select('count(subscribed.id)');
            $qb1->from('EventsEventsBundle:Subscribed', 'subscribed');
            $qb1->where('subscribed.eventtype1 = :bar');
            $qb1->setParameter('bar', $subscribed->getEventtype1());

            $total1 = $qb1->getQuery()->getSingleScalarResult();

            if ($exists) {
                if ($event1 != $subscribed->getEventtype1()) {
                    if ($total1 > $maxfood || $total1 == $maxfood) {
                        $this->container->get('session')->getFlashBag()->add('error', 'The registrations are full for this Food Event. Please choose another time slot for the Food Event');
                        return array('form' => $form->createView());
                    }
                }
            } else {
                if ($total1 > $maxfood || $total1 == $maxfood) {
                    $this->container->get('session')->getFlashBag()->add('error', 'The registrations are full for this Food Event. Please choose another time slot for the Food Event');
                    return array('form' => $form->createView());
                }
            }

            $qb2 = $em->createQueryBuilder();
            $qb2->select('count(subscribed.id)');
            $qb2->from('EventsEventsBundle:Subscribed', 'subscribed');
            $qb2->where('subscribed.eventtype2 = :bar');
            $qb2->setParameter('bar', $subscribed->getEventtype2());

            $total2 = $qb2->getQuery()->getSingleScalarResult();
            if ($exists) {
                if ($event2 != $subscribed->getEventtype2()) {
                    if ($total2 > $max || $total2 == $max) {
                        $this->container->get('session')->getFlashBag()->add('error', 'The registrations are full for the selected Cultural Event 1.Please choose another event');
                        return array('form' => $form->createView());
                    }
                }
            } else {
                if ($total2 > $max || $total2 == $max) {
                    $this->container->get('session')->getFlashBag()->add('error', 'The registrations are full for the selected Cultural Event 2.Please choose another event');
                    return array('form' => $form->createView());
                }
            }

            $qb3 = $em->createQueryBuilder();
            $qb3->select('count(subscribed.id)');
            $qb3->from('EventsEventsBundle:Subscribed', 'subscribed');
            $qb3->where('subscribed.eventtype3 = :bar');
            $qb3->setParameter('bar', $subscribed->getEventtype3());

            $total3 = $qb3->getQuery()->getSingleScalarResult();

            if ($exists) {
                if ($event3 != $subscribed->getEventtype3()) {
                    if ($total3 > $max || $total3 == $max) {
                        $this->container->get('session')->getFlashBag()->add('error', 'The registrations are full for the selected Cultural Event 2.Please choose another event');
                        return array('form' => $form->createView());
                    }
                }
            } else {
                if ($total3 > $max || $total3 == $max) {
                    $this->container->get('session')->getFlashBag()->add('error', 'The registrations are full for the selected Cultural Event 2.Please choose another event');
                    return array('form' => $form->createView());
                }
            }
        }


        if ($form->isValid()) {

            $sub = $em->getRepository('EventsEventsBundle:Subscribed')->findOneBy(array('user' => $user->getId()));
            $eventtype1 = $em->getRepository('EventsEventsBundle:Eventtype')->findOneBy(array('id' => $subscribed->getEventtype1()));
            $eventtype2 = $em->getRepository('EventsEventsBundle:Eventtype')->findOneBy(array('id' => $subscribed->getEventtype2()));
            $eventtype3 = $em->getRepository('EventsEventsBundle:Eventtype')->findOneBy(array('id' => $subscribed->getEventtype3()));

            if (empty($sub)) {
                $subscribed->setUser($user);
                $subscribed->setEventtype1($eventtype1);
                $subscribed->setEventtype2($eventtype2);
                $subscribed->setEventtype3($eventtype3);
                $em->persist($subscribed);
                $copy = $subscribed;
            } else {
                $sub->setEventtype1($eventtype1);
                $sub->setEventtype2($eventtype2);
                $sub->setEventtype3($eventtype3);
                $em->persist($sub);
                $copy = $sub;
            }
            $em->flush();
            $route = 'securedhome';
            $url = $this->generateUrl($route);
            $this->container->get('session')->getFlashBag()->add('success', 'We have your registrations for the events on Wednesday. Thank you!');
            $message = \Swift_Message::newInstance()
                    ->setSubject('EPITA International - Your Registrations for Wednesday, 23rd March 2016')
                    ->setFrom('epitaevents2016@gmail.com')
                    ->setTo($user->getEmailCanonical())
                    ->setContentType("text/html")
                    ->setBody(
                    $this->renderView('EventsEventsBundle:Default:wednesdaymail.html.twig', array('row' => $copy)
                    ));
            $this->get('mailer')->send($message);
            return $this->redirect($url);
        }

        return array('form' => $form->createView());
    }

    /**
     * @Route("/secured/eventtwo",name="eventtwo")
     * @Template()
     */
    public function eventtwoAction(Request $request) {

        $exists = false;

        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return $this->redirect($this->generateUrl('events_events_default_index'));
        }
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('EventsEventsBundle:User')->find($this->get('security.context')->getToken()->getUser()->getId());

        if (!is_object($user) || !$user instanceof User) {
            throw new \Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException('This user does not have access to this section.');
        }

        $subrecord = $em->getRepository('EventsEventsBundle:Subscribed')->findOneBy(array('user' => $user->getId()));

        if (!empty($subrecord)) {
            $exists = true;
            if ($subrecord->getEventtype4() != null || $subrecord->getEventtype4() != '') {
                $event4 = $subrecord->getEventtype4()->getId();
            } else {
                $event4 = '';
            }
            if ($subrecord->getEventtype5() != null || $subrecord->getEventtype5() != '') {
                $event5 = $subrecord->getEventtype5()->getId();
            } else {
                $event5 = '';
            }
            if (($subrecord->getEventtype6() != null || $subrecord->getEventtype6() != '')) {
                $event6 = $subrecord->getEventtype6()->getId();
            } else {
                $event6 = '';
            }
            if (($subrecord->getEventtype7() != null || $subrecord->getEventtype7() != '')) {
                $event7 = $subrecord->getEventtype7()->getId();
            } else {
                $event7 = '';
            }
        }

        $subscribed = new Subscribed();

        $form = $this->createForm(new EventtwoType($subrecord), $subscribed);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            if ($subscribed->getEventtype4() == null && $subscribed->getEventtype5() == null ||
                    $subscribed->getEventtype4() == null && $subscribed->getEventtype6() == null ||
                    $subscribed->getEventtype4() == null && $subscribed->getEventtype7() == null ||
                    $subscribed->getEventtype5() == null && $subscribed->getEventtype6() == null ||
                    $subscribed->getEventtype5() == null && $subscribed->getEventtype7() == null ||
                    $subscribed->getEventtype6() == null && $subscribed->getEventtype7() == null
            ) {
                //User did not choose both 3 events
                $this->container->get('session')->getFlashBag()->add('error', 'Oh oh! It is mandatory to choose atleast 3 different events for the given day');
                return array('form' => $form->createView());
            }

            if ($subscribed->getEventtype4() == 34 && $subscribed->getEventtype5() == 34 ||
                    $subscribed->getEventtype4() == 34 && $subscribed->getEventtype6() == 34 ||
                    $subscribed->getEventtype4() == 34 && $subscribed->getEventtype7() == 34 ||
                    $subscribed->getEventtype5() == 34 && $subscribed->getEventtype6() == 34 ||
                    $subscribed->getEventtype5() == 34 && $subscribed->getEventtype7() == 34 ||
                    $subscribed->getEventtype6() == 34 && $subscribed->getEventtype7() == 34
            ) {
                //User did not choose both 3 events
                $this->container->get('session')->getFlashBag()->add('error', 'Oh oh! It is mandatory to choose atleast 3 different events for the given day');
                return array('form' => $form->createView());
            }

            if ($subscribed->getEventtype4() != 34 &&
                    $subscribed->getEventtype5() != 34 &&
                    $subscribed->getEventtype6() != 34 &&
                    $subscribed->getEventtype7() != 34
            ) {
                $this->container->get('session')->getFlashBag()->add('error', 'Oh oh! You can select only 3 events for the given day');
                return array('form' => $form->createView());
            }

            //Identical events should not be selected
            if (($subscribed->getEventtype4() == 17 && $subscribed->getEventtype5() == 20) ||
                    ($subscribed->getEventtype4() == 18 && $subscribed->getEventtype5() == 21) ||
                    ($subscribed->getEventtype6() == 22 && $subscribed->getEventtype7() == 24) ||
                    ($subscribed->getEventtype6() == 23 && $subscribed->getEventtype7() == 25)
            ) {
                //User chose identical events
                $this->container->get('session')->getFlashBag()->add('error', 'Oh no! Not the same event twice. Please choose another event.');
                return array('form' => $form->createView());
            }


            $maxconf = $this->container->getParameter('max_conf');

            //Now check for the participants limit
            $qb0 = $em->createQueryBuilder();
            $qb0->select('count(subscribed.id)');
            $qb0->from('EventsEventsBundle:Subscribed', 'subscribed');
            $qb0->where('subscribed.eventtype4 = :bar');
            $qb0->setParameter('bar', $subscribed->getEventtype4());

            $total0 = $qb0->getQuery()->getSingleScalarResult();

            if ($exists) {
                if ($event4 != $subscribed->getEventtype4()) {
                    if ($total0 > $maxconf || $total0 == $maxconf) {
                        $this->container->get('session')->getFlashBag()->add('error', 'The registrations are full for the selected Workshop/Conference Events 1. Please choose another event');
                        return array('form' => $form->createView());
                    }
                }
            } else {
                if ($total0 > $maxconf || $total0 == $maxconf) {
                    $this->container->get('session')->getFlashBag()->add('error', 'The registrations are full for the selected Workshop/Conference Events 1. Please choose another event');
                    return array('form' => $form->createView());
                }
            }

            $qb1 = $em->createQueryBuilder();
            $qb1->select('count(subscribed.id)');
            $qb1->from('EventsEventsBundle:Subscribed', 'subscribed');
            $qb1->where('subscribed.eventtype5 = :bar');
            $qb1->setParameter('bar', $subscribed->getEventtype5());

            $total1 = $qb1->getQuery()->getSingleScalarResult();

            if ($exists) {
                if ($event5 != $subscribed->getEventtype5()) {
                    if ($total1 > $maxconf || $total1 == $maxconf) {
                        $this->container->get('session')->getFlashBag()->add('error', 'The registrations are full for the selected Workshop/Conference Events 2. Please choose another event');
                        return array('form' => $form->createView());
                    }
                }
            } else {
                if ($total1 > $maxconf || $total1 == $maxconf) {
                    $this->container->get('session')->getFlashBag()->add('error', 'The registrations are full for the selected Workshop/Conference Events 2. Please choose another event');
                    return array('form' => $form->createView());
                }
            }

            $qb2 = $em->createQueryBuilder();
            $qb2->select('count(subscribed.id)');
            $qb2->from('EventsEventsBundle:Subscribed', 'subscribed');
            $qb2->where('subscribed.eventtype6 = :bar');
            $qb2->setParameter('bar', $subscribed->getEventtype6());

            $total2 = $qb2->getQuery()->getSingleScalarResult();
            if ($exists) {
                if ($event6 != $subscribed->getEventtype6()) {
                    if ($total2 > $maxconf || $total2 == $maxconf) {
                        $this->container->get('session')->getFlashBag()->add('error', 'The registrations are full for the selected Workshop/Conference Events 3.Please choose another event');
                        return array('form' => $form->createView());
                    }
                }
            } else {
                if ($total2 > $maxconf || $total2 == $maxconf) {
                    $this->container->get('session')->getFlashBag()->add('error', 'The registrations are full for the selected Workshop/Conference Events 3.Please choose another event');
                    return array('form' => $form->createView());
                }
            }

            $qb3 = $em->createQueryBuilder();
            $qb3->select('count(subscribed.id)');
            $qb3->from('EventsEventsBundle:Subscribed', 'subscribed');
            $qb3->where('subscribed.eventtype7 = :bar');
            $qb3->setParameter('bar', $subscribed->getEventtype7());

            $total3 = $qb3->getQuery()->getSingleScalarResult();

            if ($exists) {
                if ($event7 != $subscribed->getEventtype7()) {
                    if ($total3 > $maxconf || $total3 == $maxconf) {
                        $this->container->get('session')->getFlashBag()->add('error', 'The registrations are full for the selected Workshop/Conference Events 4. Please choose another event');
                        return array('form' => $form->createView());
                    }
                }
            } else {
                if ($total3 > $maxconf || $total3 == $maxconf) {
                    $this->container->get('session')->getFlashBag()->add('error', 'The registrations are full for the selected Workshop/Conference Events 4. Please choose another event');
                    return array('form' => $form->createView());
                }
            }
        }

        if ($form->isValid()) {

            $sub = $em->getRepository('EventsEventsBundle:Subscribed')->findOneBy(array('user' => $user->getId()));
            $eventtype4 = $em->getRepository('EventsEventsBundle:Eventtype')->findOneBy(array('id' => $subscribed->getEventtype4()));
            $eventtype5 = $em->getRepository('EventsEventsBundle:Eventtype')->findOneBy(array('id' => $subscribed->getEventtype5()));
            $eventtype6 = $em->getRepository('EventsEventsBundle:Eventtype')->findOneBy(array('id' => $subscribed->getEventtype6()));
            $eventtype7 = $em->getRepository('EventsEventsBundle:Eventtype')->findOneBy(array('id' => $subscribed->getEventtype7()));
            if (empty($sub)) {
                $subscribed->setUser($user);
                $subscribed->setEventtype4($eventtype4);
                $subscribed->setEventtype5($eventtype5);
                $subscribed->setEventtype6($eventtype6);
                $subscribed->setEventtype7($eventtype7);
                $em->persist($subscribed);
                $copy = $subscribed;
            } else {
                $sub->setEventtype4($eventtype4);
                $sub->setEventtype5($eventtype5);
                $sub->setEventtype6($eventtype6);
                $sub->setEventtype7($eventtype7);
                $em->persist($sub);
                $copy = $sub;
            }
            $em->flush();
            $route = 'securedhome';
            $url = $this->generateUrl($route);
            $this->container->get('session')->getFlashBag()->add('success', 'We have your registrations for the events on Thursday. Thank you!');
            $message = \Swift_Message::newInstance()
                    ->setSubject('EPITA International - Your Registrations for Thursday, 24th March 2016')
                    ->setFrom('epitaevents2016@gmail.com')
                    ->setTo($user->getEmailCanonical())
                    ->setContentType("text/html")
                    ->setBody(
                    $this->renderView('EventsEventsBundle:Default:thursdaymail.html.twig', array('row' => $copy)
                    ));
            $this->get('mailer')->send($message);
            return $this->redirect($url);
        }

        return array('form' => $form->createView());
    }

    /**
     * @Route("/secured/eventthree",name="eventthree")
     * @Template()
     */
    public function eventthreeAction(Request $request) {

        $exists = false;

        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return $this->redirect($this->generateUrl('events_events_default_index'));
        }
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('EventsEventsBundle:User')->find($this->get('security.context')->getToken()->getUser()->getId());

        if (!is_object($user) || !$user instanceof User) {
            throw new \Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException('This user does not have access to this section.');
        }

        $subrecord = $em->getRepository('EventsEventsBundle:Subscribed')->findOneBy(array('user' => $user->getId()));

        if (!empty($subrecord)) {
            $exists = true;
            if ($subrecord->getEventtype8() != null || $subrecord->getEventtype8() != '') {
                $event8 = $subrecord->getEventtype8()->getId();
            } else {
                $event8 = '';
            }
            if ($subrecord->getEventtype9() != null || $subrecord->getEventtype9() != '') {
                $event9 = $subrecord->getEventtype9()->getId();
            } else {
                $event9 = '';
            }
            if (($subrecord->getEventtype10() != null || $subrecord->getEventtype10() != '')) {
                $event10 = $subrecord->getEventtype10()->getId();
            } else {
                $event10 = '';
            }
        }

        $subscribed = new Subscribed();

        $form = $this->createForm(new EventthreeType($subrecord), $subscribed);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            if ($subscribed->getEventtype8() == null ||
                    $subscribed->getEventtype9() == null ||
                    $subscribed->getEventtype10() == null
            ) {
                //User did not choose both 3 events
                $this->container->get('session')->getFlashBag()->add('error', 'Oh oh! It is mandatory to choose atleast 3 different events for the given day');
                return array('form' => $form->createView());
            }

            $maxconf = $this->container->getParameter('max_uni');
            $maxconf1 = $this->container->getParameter('max_uni2');

            //Now check for the participants limit
            $qb0 = $em->createQueryBuilder();
            $qb0->select('count(subscribed.id)');
            $qb0->from('EventsEventsBundle:Subscribed', 'subscribed');
            $qb0->where('subscribed.eventtype4 = :bar');
            $qb0->setParameter('bar', $subscribed->getEventtype8());

            $total0 = $qb0->getQuery()->getSingleScalarResult();

            if ($exists) {
                if ($event8 != $subscribed->getEventtype8()) {
                    if ($total0 > $maxconf || $total0 == $maxconf) {
                        $this->container->get('session')->getFlashBag()->add('error', 'The registrations are full for the selected University Presentation 1. Please choose another event');
                        return array('form' => $form->createView());
                    }
                }
            } else {
                if ($total0 > $maxconf || $total0 == $maxconf) {
                    $this->container->get('session')->getFlashBag()->add('error', 'The registrations are full for the selected University Presentation 1. Please choose another event');
                    return array('form' => $form->createView());
                }
            }

            $qb1 = $em->createQueryBuilder();
            $qb1->select('count(subscribed.id)');
            $qb1->from('EventsEventsBundle:Subscribed', 'subscribed');
            $qb1->where('subscribed.eventtype9 = :bar');
            $qb1->setParameter('bar', $subscribed->getEventtype9());

            $total1 = $qb1->getQuery()->getSingleScalarResult();

            if ($exists) {
                if ($event9 != $subscribed->getEventtype9()) {
                    if ($total1 > $maxconf || $total1 == $maxconf) {
                        $this->container->get('session')->getFlashBag()->add('error', 'The registrations are full for the selected University Presentation 2. Please choose another event');
                        return array('form' => $form->createView());
                    }
                }
            } else {
                if ($total1 > $maxconf || $total1 == $maxconf) {
                    $this->container->get('session')->getFlashBag()->add('error', 'The registrations are full for the selected University Presentation 2. Please choose another event');
                    return array('form' => $form->createView());
                }
            }

            $qb2 = $em->createQueryBuilder();
            $qb2->select('count(subscribed.id)');
            $qb2->from('EventsEventsBundle:Subscribed', 'subscribed');
            $qb2->where('subscribed.eventtype10 = :bar');
            $qb2->setParameter('bar', $subscribed->getEventtype10());

            $total2 = $qb2->getQuery()->getSingleScalarResult();
            if ($exists) {
                if ($event10 != $subscribed->getEventtype10()) {
                    if ($total2 > $maxconf1 || $total2 == $maxconf1) {
                        $this->container->get('session')->getFlashBag()->add('error', 'The registrations are full for the selected University Presentation 3.Please choose another event');
                        return array('form' => $form->createView());
                    }
                }
            } else {
                if ($total2 > $maxconf1 || $total2 == $maxconf1) {
                    $this->container->get('session')->getFlashBag()->add('error', 'The registrations are full for the selected University Presentation 3.Please choose another event');
                    return array('form' => $form->createView());
                }
            }
        }

        if ($form->isValid()) {

            $sub = $em->getRepository('EventsEventsBundle:Subscribed')->findOneBy(array('user' => $user->getId()));
            $eventtype8 = $em->getRepository('EventsEventsBundle:Eventtype')->findOneBy(array('id' => $subscribed->getEventtype8()));
            $eventtype9 = $em->getRepository('EventsEventsBundle:Eventtype')->findOneBy(array('id' => $subscribed->getEventtype9()));
            $eventtype10 = $em->getRepository('EventsEventsBundle:Eventtype')->findOneBy(array('id' => $subscribed->getEventtype10()));

            if (empty($sub)) {
                $subscribed->setUser($user);
                $subscribed->setEventtype4($eventtype8);
                $subscribed->setEventtype5($eventtype9);
                $subscribed->setEventtype6($eventtype10);
                $em->persist($subscribed);
                $copy = $subscribed;
            } else {
                $sub->setEventtype4($eventtype8);
                $sub->setEventtype5($eventtype9);
                $sub->setEventtype6($eventtype10);
                $em->persist($sub);
                $copy = $sub;
            }
            $em->flush();
            $route = 'securedhome';
            $url = $this->generateUrl($route);
            $this->container->get('session')->getFlashBag()->add('success', 'We have your registrations for the events on Friday. Thank you!');
            $message = \Swift_Message::newInstance()
                    ->setSubject('EPITA International - Your Registrations for Friday, 25th March 2016')
                    ->setFrom('epitaevents2016@gmail.com')
                    ->setTo($user->getEmailCanonical())
                    ->setContentType("text/html")
                    ->setBody(
                    $this->renderView('EventsEventsBundle:Default:fridaymail.html.twig', array('row' => $copy)
                    ));
            $this->get('mailer')->send($message);
            return $this->redirect($url);
        }

        return array('form' => $form->createView());
    }

    /**
     *
     * @Route("/export/wednesday",name="exportwed")
     *      
     */
    public function exportwedAction() {

        $format = 'xls';

        $filename = sprintf('export_students_wednesday.%s', $format);

        $data = array();
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT s FROM Events\Bundle\EventsBundle\Entity\Subscribed s');
        $data = $query->getResult();
        $content = $this->renderView('EventsEventsBundle:Default:wednesday.html.twig', array('data' => $data));
        $response = new Response($content);
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);
        $response->send();

        return new Response($content);
    }
    /**
     *
     * @Route("/export/thursday",name="exportthu")
     *      
     */
    public function exportthuAction() {

        $format = 'xls';

        $filename = sprintf('export_students_thursday.%s', $format);

        $data = array();
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT s FROM Events\Bundle\EventsBundle\Entity\Subscribed s');
        $data = $query->getResult();
        $content = $this->renderView('EventsEventsBundle:Default:thursday.html.twig', array('data' => $data));
        $response = new Response($content);
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);
        $response->send();

        return new Response($content);
    }

    /**
     *
     * @Route("/export/friday",name="exportfri")
     *      
     */
    public function exportfriAction() {

        $format = 'xls';

        $filename = sprintf('export_students_friday.%s', $format);

        $data = array();
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT s FROM Events\Bundle\EventsBundle\Entity\Subscribed s');
        $data = $query->getResult();
        $content = $this->renderView('EventsEventsBundle:Default:friday.html.twig', array('data' => $data));
        $response = new Response($content);
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);
        $response->send();

        return new Response($content);
    }

    /**
     * Authenticate the user
     * 
     * @param FOS\UserBundle\Model\UserInterface
     */
    protected function authenticateUser(User $user) {
        try {
            $this->container->get('security.user_checker')->checkPostAuth($user);
        } catch (AccountStatusException $e) {
            // Don't authenticate locked, disabled or expired users
            return;
        }

        $providerKey = $this->container->getParameter('fos_user.firewall_name');
        $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken($user, null, $providerKey, $user->getRoles());
        $this->container->get('security.context')->setToken($token);
    }

}