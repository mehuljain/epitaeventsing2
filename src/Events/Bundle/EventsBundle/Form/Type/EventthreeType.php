<?php

namespace Events\Bundle\EventsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Events\Bundle\EventsBundle\Entity\Subscribed;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EventthreeType extends AbstractType {
    
    protected $subscribed;
    
    public function __construct($subscribed){
        
        $this->subscribed = $subscribed;
    }



    public function buildForm(FormBuilderInterface $builder, array $options) {
        
       if (!empty($this->subscribed)){
           if($this->subscribed->getEventtype8() == null){
                $eventtype8 = '';   
           }
           else {
               $eventtype8 = $this->subscribed->getEventtype8()->getId();
           }
           if($this->subscribed->getEventtype9() == null){
                $eventtype9 = '';   
           }
           else {
               $eventtype9 = $this->subscribed->getEventtype9()->getId();
           }
           if($this->subscribed->getEventtype10() == null){
                $eventtype10 = '';   
           }
           else {
               $eventtype10 = $this->subscribed->getEventtype10()->getId();
           }
           
       }
       else {
           $eventtype8 = '';
           $eventtype9 = '';
           $eventtype10 = '';
       }
       //Eventtype8
        $builder->add('eventtype8','choice',array(
            'choices' => array('26' => 'Dual Degree Griffith College Dublin', 
                               '27' => 'Dual Degree Boston',
                               '28' => 'Study at Oxford Brookes'),                
            'expanded' => true,
            'multiple' => false,
            'label' => 'University Presentation 1, Time 11am - 12noon',
            'required' => true,
            'data' =>  $eventtype8,           
        ));
       //Eventtype5
        $builder->add('eventtype9','choice',array(
            'choices' => array('29' => 'Study at CSUCI', 
                               '30' => 'Study at CSUMB',
                               '31'=> 'Dual Degree Stevens'),
            'expanded' => true,
            'multiple' => false,
            'label' => 'University Presentation 2, Time 2pm - 3pm',
            'required' => true,
            'data' =>  $eventtype9,
        ));
        
       //Eventtype6
        $builder->add('eventtype10','choice',array(
            'choices' => array('32' => 'Study at Ahlia', 
                               '33' => 'Study at Stafford'
                ),
            'expanded' => true,
            'multiple' => false,
            'label' => 'University Presentation 3, 4pm – 5pm',
            'required' => true,
            'data' => $eventtype10,
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