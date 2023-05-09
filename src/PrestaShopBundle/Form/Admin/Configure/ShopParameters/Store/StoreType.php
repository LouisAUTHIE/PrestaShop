<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

namespace PrestaShopBundle\Form\Admin\Configure\ShopParameters\Store;

use PrestaShopBundle\Form\Admin\Type\GeoCoordinatesType;
use Symfony\Component\Routing\RouterInterface;
use PrestaShopBundle\Form\Admin\Type\EmailType;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use PrestaShopBundle\Form\Admin\Type\TranslatableType;
use Symfony\Contracts\Translation\TranslatorInterface;
use PrestaShopBundle\Form\Admin\Type\CountryChoiceType;
use PrestaShopBundle\Form\Admin\Type\ShopChoiceTreeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\NoTags;
use PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\CleanHtml;
use PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\TypedRegex;
use PrestaShop\PrestaShop\Core\Form\ConfigurableFormChoiceProviderInterface;
use PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\AddressZipCode;
use PrestaShop\PrestaShop\Core\Domain\Address\Configuration\AddressConstraint;
use PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\DefaultLanguage;
use PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\AddressStateRequired;

/**
 * Class StoreType
 */
class StoreType extends TranslatorAwareType
{
    public const MAX_TITLE_LENGTH = 255;

    /**
     * @var bool
     */
    private $isShopFeatureEnabled;

    /**
     * @var ConfigurableFormChoiceProviderInterface
     */
    private $stateChoiceProvider;

    /**
     * @var int
     */
    private $contextCountryId;

    /**
     * @var RouterInterface
     */
    private $router;    

    /**
     * @param TranslatorInterface $translator
     * @param array $locales
     * @param bool $isShopFeatureEnabled
     * @param ConfigurableFormChoiceProviderInterface $stateChoiceProvider
     * @param int $contextCountryId
     * @param RouterInterface $router
     */
    public function __construct(
        TranslatorInterface $translator,
        array $locales,
        $isShopFeatureEnabled,
        ConfigurableFormChoiceProviderInterface $stateChoiceProvider,
        $contextCountryId,
        RouterInterface $router
    ) {
        parent::__construct($translator, $locales);
        $this->isShopFeatureEnabled = $isShopFeatureEnabled;
        $this->stateChoiceProvider = $stateChoiceProvider;
        $this->contextCountryId = $contextCountryId;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $data = $builder->getData();
        $countryId = 0 !== $data['id_country'] ? $data['id_country'] : $this->contextCountryId;
        $stateChoices = $this->stateChoiceProvider->getChoices(['id_country' => $countryId]);
        $showStates = !empty($stateChoices);

        $builder
            ->add('name', TranslatableType::class, [
                'label' => $this->trans('Name', 'Admin.Global'),
                'help' => $this->trans('Store name', 'Admin.Shopparameters.Help'),
                'required' => false,
                'constraints' => [
                    new DefaultLanguage(),
                ],
                'options' => [
                    'constraints' => [
                        new Regex([
                            'pattern' => '/^[^<>={}]*$/u',
                            'message' => $this->trans(
                                '%s is invalid.',
                                'Admin.Notifications.Error'
                            ),
                        ]
                        ),
                    ],
                ],
            ])
            ->add('address1', TranslatableType::class, [
                'label' => $this->trans('Address', 'Admin.Global'),
                'constraints' => [
                    new NotBlank([
                        'message' => $this->trans(
                            'This field cannot be empty.', 'Admin.Notifications.Error'
                        ),
                    ]),
                    new CleanHtml(),
                    new TypedRegex([
                        'type' => TypedRegex::TYPE_ADDRESS,
                    ]),
                    new Length([
                        'max' => AddressConstraint::MAX_ADDRESS_LENGTH,
                        'maxMessage' => $this->trans(
                            'This field cannot be longer than %limit% characters',
                            'Admin.Notifications.Error',
                            ['%limit%' => AddressConstraint::MAX_ADDRESS_LENGTH]
                        ),
                    ]),
                ],
                'options' => [],
            ])
            ->add('address2', TranslatableType::class, [
                'label' => $this->trans('Address (2)', 'Admin.Global'),
                'required' => false,
                'constraints' => [
                    new CleanHtml(),
                    new TypedRegex([
                        'type' => TypedRegex::TYPE_ADDRESS,
                    ]),
                    new Length([
                        'max' => AddressConstraint::MAX_ADDRESS_LENGTH,
                        'maxMessage' => $this->trans(
                            'This field cannot be longer than %limit% characters',
                            'Admin.Notifications.Error',
                            ['%limit%' => AddressConstraint::MAX_ADDRESS_LENGTH]
                        ),
                    ]),
                ],
                'options' => [],
            ])
            ->add('postcode', TextType::class, [
                'label' => $this->trans('Zip/Postal code', 'Admin.Global'),
                'required' => false,
                'constraints' => [
                    new AddressZipCode([
                        'id_country' => $countryId,
                        'required' => false,
                    ]),
                    new CleanHtml(),
                    new TypedRegex([
                        'type' => TypedRegex::TYPE_POST_CODE,
                    ]),
                    new Length([
                        'max' => AddressConstraint::MAX_POSTCODE_LENGTH,
                        'maxMessage' => $this->trans(
                            'This field cannot be longer than %limit% characters',
                            'Admin.Notifications.Error',
                            ['%limit%' => AddressConstraint::MAX_POSTCODE_LENGTH]
                        ),
                    ]),
                ],
            ])
            ->add('city', TextType::class, [
                'label' => $this->trans('City', 'Admin.Global'),
                'constraints' => [
                    new NotBlank([
                        'message' => $this->trans(
                            'This field is required', 'Admin.Notifications.Error'
                        ),
                    ]),
                    new CleanHtml(),
                    new TypedRegex([
                        'type' => TypedRegex::TYPE_CITY_NAME,
                    ]),
                    new Length([
                        'max' => AddressConstraint::MAX_CITY_LENGTH,
                        'maxMessage' => $this->trans(
                            'This field cannot be longer than %limit% characters',
                            'Admin.Notifications.Error',
                            ['%limit%' => AddressConstraint::MAX_CITY_LENGTH]
                        ),
                    ]),
                ],
            ])
            ->add('id_country', CountryChoiceType::class, [
                'label' => $this->trans('Country', 'Admin.Global'),
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => $this->trans(
                            'This field cannot be empty.', 'Admin.Notifications.Error'
                        ),
                    ]),
                ],
                'attr' => [
                    'data-states-url' => $this->router->generate('admin_country_states'),
                    'data-toggle' => 'select2',
                    'data-minimumResultsForSearch' => '7',
                ],
            ])->add('id_state', ChoiceType::class, [
                'label' => $this->trans('State', 'Admin.Global'),
                'required' => true,
                'choices' => $stateChoices,
                'constraints' => [
                    new AddressStateRequired([
                        'id_country' => $countryId,
                    ]),
                ],
                'row_attr' => [
                    'class' => 'js-store-state-select',
                ],
                'attr' => [
                    'visible' => $showStates,
                    'data-toggle' => 'select2',
                    'data-minimumResultsForSearch' => '7',
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => $this->trans('Phone', 'Admin.Global'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new CleanHtml(),
                    new TypedRegex([
                        'type' => TypedRegex::TYPE_PHONE_NUMBER,
                    ]),
                    new Length([
                        'max' => AddressConstraint::MAX_PHONE_LENGTH,
                        'maxMessage' => $this->trans(
                            'This field cannot be longer than %limit% characters',
                            'Admin.Notifications.Error',
                            ['%limit%' => AddressConstraint::MAX_PHONE_LENGTH]
                        ),
                    ]),
                ],
            ])
            ->add('latlon', GeoCoordinatesType::class, [
                'label' => $this->trans('Position', 'Admin.Global'),
                'required' => false,
                'empty_data' => '',
                'label_latitude' => 'Latitude',
                'label_longitude' => 'Longitude',
                'attr' => ['class'=>'form-inline'],
            ])
            
        ;

        if ($this->isShopFeatureEnabled) {
            $builder->add('shop_association', ShopChoiceTreeType::class, [
                'label' => $this->trans('Store association', 'Admin.Global'),
                'constraints' => [
                    new NotBlank([
                        'message' => $this->trans(
                            'The %s field is required.',
                            'Admin.Notifications.Error',
                            [
                                sprintf('"%s"', $this->trans('Store association', 'Admin.Global')),
                            ]
                        ),
                    ]),
                ],
            ]);
        }
    }
}
