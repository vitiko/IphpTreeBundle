<?php
namespace Iphp\TreeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

use Doctrine\ORM\EntityManager;

use Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader;
use Symfony\Bridge\Doctrine\Form\ChoiceList\EntityChoiceList;
use Symfony\Component\Form\Extension\Core\ChoiceList\SimpleChoiceList;

use Symfony\Component\Form\Exception\TransformationFailedException;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class TreeNodeChoiceType extends AbstractType
{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }


    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $em = $this->em;
        $resolver->setDefaults(array(


            'property' => 'title',
            'empty_value' => '',
            'class' => null,
            'root' => null,
            'choice_list' => function (Options $options, $previousValue) use ($em)
            {
                $qb = $em->getRepository($options['class'])
                    ->createQueryBuilder('n')
                    ->orderBy('n.left');

                if  ($options['root']) $qb->where ('n.root = :root')->setParameter ('root',$options['root']);

                $entityChoiceList = new  EntityChoiceList ($em, $options['class'],
                    $options['property'],
                    new ORMQueryBuilderLoader ($qb));


                return $entityChoiceList;


                $choices = $entityChoiceList->getChoices();

                foreach ($choices as $key => $choice)
                    if (is_object($choice)) $choices[$key] = $choice->{'get' . $options['property']}();

                return new SimpleChoiceList ($choices);

            }


        ));

    }

    public function getParent()
    {
        return 'choice';
    }

    public function getName()
    {
        return 'treenode_choice';
    }


}

