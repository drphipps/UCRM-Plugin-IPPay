<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\CustomAttribute;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\InvoiceAttribute;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoiceAttributesType extends AbstractType
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
            function (FormEvent $event) {
                /** @var InvoiceAttribute $attribute */
                foreach ($event->getData() as $attribute) {
                    $event->getForm()
                        ->get($attribute->getAttribute()->getKey())
                        ->setData($attribute->getValue());
                }
            }
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($customAttributesMap, $options) {
                /** @var Invoice $invoice */
                $invoice = $options['invoice'];

                $invoiceAttributesMap = [];
                /** @var InvoiceAttribute $attribute */
                foreach ($event->getData() as $attribute) {
                    $invoiceAttributesMap[$attribute->getAttribute()->getKey()] = $attribute;
                }

                foreach ($event->getForm() as $key => $field) {
                    $data = $field->getData();
                    $attribute = $invoiceAttributesMap[$key] ?? null;

                    if ($data !== null) {
                        if (! $attribute) {
                            $attribute = new InvoiceAttribute();
                            $attribute->setAttribute($customAttributesMap[$key]);
                            $invoice->addAttribute($attribute);
                        }
                        $attribute->setValue($field->getData());
                    } elseif ($attribute) {
                        $invoice->removeAttribute($attribute);
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
        $resolver->setRequired('invoice');
        $resolver->setAllowedTypes('invoice', Invoice::class);
    }
}
