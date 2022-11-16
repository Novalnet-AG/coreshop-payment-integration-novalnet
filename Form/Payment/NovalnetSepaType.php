<?php
/**
 * Novalnet payment bundle
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Novalnet End User License Agreement
 * that is bundled with this package in the file LICENSE.txt
 *
 * DISCLAIMER
 *
 * If you wish to customize Novalnet payment bundle for your needs,
 * please contact technic@novalnet.de for more information.
 *
 * @category   Novalnet
 * @package    Novalnet_Payment
 * @copyright  Copyright (c) Novalnet AG
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 */

declare(strict_types=1);

namespace NovalnetBundle\Form\Payment;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Pimcore\Model\Element\ValidationException;

final class NovalnetSepaType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('test_mode', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'groups' => 'coreshop',
                    ]),
                ],
            ])
            ->add('due_days', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'groups' => 'coreshop',
                    ]),
                ],
            ])
            ->add('payment_action', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'groups' => 'coreshop',
                    ]),
                ],
            ])
            ->add('use_authorize', CheckboxType::class, [

            ])
            ->add('min_authorize_amount', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'groups' => 'coreshop',
                    ]),
                ],
            ])
            ->add('completed_status', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'groups' => 'coreshop',
                    ]),
                ],
            ])
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();

                $novalnetHelper = \Pimcore::getContainer()->get(\NovalnetBundle\Helper\NovalnetHelper::class);
                $locale = $novalnetHelper->getLanguageCode();

                if (!empty($data['min_authorize_amount']) && !$novalnetHelper->checkIsNumeric($data['min_authorize_amount'])) {
                    $errMsg = ($locale == 'DE') ? 'Bitte geben Sie eine gültige Zahl in dieses Feld ein Mindesttransaktionsbetrag für die Autorisierung' :
                        'Please enter a valid number in this field Minimum transaction amount for authorization';

                    throw new ValidationException($errMsg);
                }

                if (!empty($data['due_days']) && !$novalnetHelper->checkIsNumeric($data['due_days'])) {
                    $errMsg = ($locale == 'DE') ? 'Bitte geben Sie eine gültige Anzahl an Tagen in dieses Feld ein "Fälligkeitsdatum (in Tagen)"' :
                        'Please enter a valid number (in days) in this field "Payment due date (in days)"';

                    throw new ValidationException($errMsg);
                }

                $useAuthorize = false;
                if (isset($data['payment_action']) && $data['payment_action'] == '1') {
                    $useAuthorize = true;
                }

                $data['use_authorize'] = $useAuthorize;
                $event->setData($data);
            });
    }
}
