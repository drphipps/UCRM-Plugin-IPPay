<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Client;
use AppBundle\Entity\ClientAttribute;
use AppBundle\Entity\CustomAttribute;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\OutOfBoundsException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientAttributesType extends AbstractType
{
    /**
     * @var CustomAttribute[]
     */
    private $attributes;

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $customAttributesMap = [];
        foreach ($this->attributes as $attribute) {
            $this->addField($builder, $attribute);
            $customAttributesMap[$attribute->getKey()] = $attribute;
        }

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            static function (FormEvent $event) {
                /** @var ClientAttribute $attribute */
                foreach ($event->getData() as $attribute) {
                    try {
                        $event->getForm()
                            ->get($attribute->getAttribute()->getKey())
                            ->setData($attribute->getValue());
                    } catch (OutOfBoundsException $outOfBoundsException) {
                        // race condition: attribute was deleted from a different process, ignore
                        continue;
                    }
                }
            }
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            static function (FormEvent $event) use ($customAttributesMap, $options) {
                /** @var Client $client */
                $client = $options['client'];

                $clientAttributesMap = [];
                /** @var ClientAttribute $attribute */
                foreach ($event->getData() as $attribute) {
                    $clientAttributesMap[$attribute->getAttribute()->getKey()] = $attribute;
                }

                foreach ($event->getForm() as $key => $field) {
                    $data = $field->getData();
                    $attribute = $clientAttributesMap[$key] ?? null;

                    if ($data !== null) {
                        if (! $attribute) {
                            $attribute = new ClientAttribute();
                            $attribute->setAttribute($customAttributesMap[$key]);
                            $client->addAttribute($attribute);
                        }
                        $attribute->setValue($field->getData());
                    } elseif ($attribute) {
                        $client->removeAttribute($attribute);
                    }
                }
            }
        );
    }

    private function addField(FormBuilderInterface $builder, CustomAttribute $attribute): void
    {
        switch ($attribute->getType()) {
            case CustomAttribute::TYPE_STRING:
                $builder->add(
                    $attribute->getKey(),
                    TextType::class,
                    [
                        'label' => $attribute->getName(),
                        'translation_domain' => false,
                        'required' => false,
                        'mapped' => false,
                    ]
                );

                break;
            default:
                throw new \InvalidArgumentException();
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('client');
        $resolver->setAllowedTypes('client', Client::class);
    }
}
