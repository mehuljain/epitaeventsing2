<?php

namespace Events\Bundle\EventsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Events\Bundle\EventsBundle\Entity\Subscribed;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EventtwoType extends AbstractType {
    
    protected $subscribed;
    
    public function __construct($subscribed){
        
        $this->subscribed = $subscribed;
    }



    public function buildForm(FormBuilderInterface $builder, array $options) {
        
       if (!empty($this->subscribed)){
           if($this->subscribed->getEventtype4() == null){
                $eventtype4 = '';   
           }
           else {
               $eventtype4 = $this->subscribed->getEventtype4()->getId();
           }
           if($this->subscribed->getEventtype5() == null){
                $eventtype5 = '';   
           }
           else {
               $eventtype5 = $this->subscribed->getEventtype5()->getId();
           }
           if($this->subscribed->getEventtype6() == null){
                $eventtype6 = '';   
           }
           else {
               $eventtype6 = $this->subscribed->getEventtype6()->getId();
           }
           if($this->subscribed->getEventtype7() == null){
                $eventtype7 = '';   
           }
           else {
               $eventtype7 = $this->subscribed->getEventtype7()->getId();
           }    
       }
       else {
           $eventtype4 = '';
           $eventtype5 = '';
           $eventtype6 = '';
           $eventtype7 = '';
       }
       //Eventtype4
        $builder->add('eventtype4','choice',array(
            'choices' => array('17' => 'Mobilité Internationale Contextes Interculturels 1', 
                               '18' => 'L’interculturalité sans risques 1',
                               '19' => 'Travailler à Hong Kong',        
                               '34' => 'None of the above'),                
            'expanded' => true,
            'multiple' => false,
            'label' => 'Workshop/Conference Events 1, Time 9:30-11am/10-11:30am',
            'required' => true,
            'data' =>  $eventtype4,           
        ));
       //Eventtype5
        $builder->add('eventtype5','choice',array(
            'choices' => array('20' => 'Mobilité Internationale Contextes Interculturels 2', 
                               '21' => 'L’interculturalité sans risques 2',
                               '34'=> 'None of the above'),
            'expanded' => true,
            'multiple' => false,
            'label' => 'Workshop/Conference Events 1, Time 11 am - 12:30 pm',
            'required' => true,
            'data' =>  $eventtype5,
        ));
        
       //Eventtype6
        $builder->add('eventtype6','choice',array(
            'choices' => array('22' => 'Find an internship in Asia/States of the Gulf/Oceania 1', 
                               '23' => 'Dual degrees: a real asset ? 1',
                               '34' => 'None of the above'
                ),
            'expanded' => true,
            'multiple' => false,
            'label' => 'Workshop/Conference Events 2, 2 pm – 3:30 pm/ 2 pm- 3pm',
            'required' => true,
            'data' => $eventtype6,
        ));
        
       //Eventtype7
        $builder->add('eventtype7','choice',array(
            'choices' => array('24' => 'Find an internship in Asia/States of the Gulf/Oceania 2', 
                               '25' => 'Dual degrees: a real asset ? 2',
                               '34' => 'None of the above'
                               ),
            'expanded' => true,
            'multiple' => false,
            'label' => 'Workshop/Conference Events 3, 3:30 – 5 pm / 3 pm – 4 pm',
            'required' => true,
            'data' => $eventtype7,
        ));
        
    }

    public function getDefaultOptions(array $options) {
        return array('csrf_protection' => true);
    }

    public function getName() {
        return 'eventtwo';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
         $resolver->setDefaults(array(
            'data_class' => 'Events\Bundle\EventsBundle\Entity\Subscribed',
        ));
    }
}