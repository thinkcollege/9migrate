<?php

namespace Drupal\surveynodeform\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\CronInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use \DateTime;
use Drupal\node\NodeInterface;

/**
 * Form with examples on how to use cron
 */
class SurveynodeformConfigurationForm extends ConfigFormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The cron service.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * The queue object.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  protected function getEditableConfigNames() {
    return array('system.site');
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $current_user, CronInterface $cron, QueueFactory $queue, StateInterface $state) {
    parent::__construct($config_factory);
    $this->currentUser = $current_user;
    $this->cron = $cron;
    $this->queue = $queue;
    $this->state = $state;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = new static(
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('cron'),
      $container->get('queue'),
      $container->get('state')
    );
    $form->setMessenger($container->get('messenger'));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'surveynodeform';
  }
  function surveynodeform_admin() {
    $form = array();
    $form['surveynodeform_maminwage'] = array(
      '#type' => 'textfield',
      '#title' => t('Massachusetts minimum wage'),
      '#default_value' => \Drupal::state()->get('surveynodeform_maminwage', 11.00),
      '#size' => 5,
      '#maxlength' => 6,
      '#description' => t("Minimum wage in Massachusetts."),
      '#required' => TRUE,
    );
    $form['surveynodeform_mahrlow'] = array(
      '#type' => 'textfield',
      '#title' => t('Massachusetts hour range, low point'),
      '#default_value' => \Drupal::state()->get('surveynodeform_mahrlow', 1),
      '#size' => 5,
      '#maxlength' => 6,
      '#description' => t("Massachusetts hour range, low point."),
      '#required' => TRUE,
    );

    $form['surveynodeform_mahrhigh'] = array(
      '#type' => 'textfield',
      '#title' => t('Massachusetts hour range, high point'),
      '#default_value' => \Drupal::state()->get('surveynodeform_mahrhigh', 80),
      '#size' => 5,
      '#maxlength' => 6,
      '#description' => t("Massachusetts hour range, high point."),
      '#required' => TRUE,
    );

    $form['surveynodeform_mawagelow'] = array(
      '#type' => 'textfield',
      '#title' => t('Massachusetts wage range, low point'),
      '#default_value' => \Drupal::state()->get('surveynodeform_mawagelow', 1),
      '#size' => 5,
      '#maxlength' => 6,
      '#description' => t("Massachusetts wage range, low point."),
      '#required' => TRUE,
    );

    $form['surveynodeform_mawagehigh'] = array(
      '#type' => 'textfield',
      '#title' => t('Massachusetts wage range, high point'),
      '#default_value' => \Drupal::state()->get('surveynodeform_mawagehigh', 1000),
      '#size' => 5,
      '#maxlength' => 6,
      '#description' => t("Massachusetts wage range, high point."),
      '#required' => TRUE,
    );



    $form['surveynodeform_maselfearnhigh'] = array(
      '#type' => 'textfield',
      '#title' => t('Massachusetts self-employment income, high point'),
      '#default_value' => \Drupal::state()->get('surveynodeform_maselfearnhigh', 1000),
      '#size' => 5,
      '#maxlength' => 6,
      '#description' => t("Massachusetts self-employment income, high point."),
      '#required' => TRUE,
    );


    $form['surveynodeform_maselfexpensehigh'] = array(
      '#type' => 'textfield',
      '#title' => t('Massachusetts self-employment expenses, high point'),
      '#default_value' => \Drupal::state()->get('surveynodeform_maselfexpensehigh', 1000),
      '#size' => 5,
      '#maxlength' => 6,
      '#description' => t("Massachusetts self-employment expenses, high point."),
      '#required' => TRUE,
    );
    // Maryland settings
    $form['surveynodeform_mdminwage'] = array(
      '#type' => 'textfield',
      '#title' => t('Maryland minimum wage'),
      '#default_value' => \Drupal::state()->get('surveynodeform_mdminwage', 8.25),
      '#size' => 5,
      '#maxlength' => 6,
      '#description' => t("Minimum wage in Maryland."),
      '#required' => TRUE,
    );
    $form['surveynodeform_mdhrlow'] = array(
      '#type' => 'textfield',
      '#title' => t('Maryland hour range, low point'),
      '#default_value' => \Drupal::state()->get('surveynodeform_mdhrlow', 1),
      '#size' => 5,
      '#maxlength' => 6,
      '#description' => t("Maryland hour range, low point."),
      '#required' => TRUE,
    );

    $form['surveynodeform_mdhrhigh'] = array(
      '#type' => 'textfield',
      '#title' => t('Maryland hour range, high point'),
      '#default_value' => \Drupal::state()->get('surveynodeform_mdhrhigh', 80),
      '#size' => 5,
      '#maxlength' => 6,
      '#description' => t("Maryland hour range, high point."),
      '#required' => TRUE,
    );

    $form['surveynodeform_mdwagelow'] = array(
      '#type' => 'textfield',
      '#title' => t('Maryland wage range, low point'),
      '#default_value' => \Drupal::state()->get('surveynodeform_mdwagelow', 1),
      '#size' => 5,
      '#maxlength' => 6,
      '#description' => t("Maryland wage range, low point."),
      '#required' => TRUE,
    );

    $form['surveynodeform_mdwagehigh'] = array(
      '#type' => 'textfield',
      '#title' => t('Maryland wage range, high point'),
      '#default_value' => \Drupal::state()->get('surveynodeform_mdwagehigh', 1000),
      '#size' => 5,
      '#maxlength' => 6,
      '#description' => t("Maryland wage range, high point."),
      '#required' => TRUE,
    );



    $form['surveynodeform_mdselfearnhigh'] = array(
      '#type' => 'textfield',
      '#title' => t('Maryland self-employment income, high point'),
      '#default_value' => \Drupal::state()->get('surveynodeform_mdselfearnhigh', 1000),
      '#size' => 5,
      '#maxlength' => 6,
      '#description' => t("Maryland self-employment income, high point."),
      '#required' => TRUE,
    );


    $form['surveynodeform_mdselfexpensehigh'] = array(
      '#type' => 'textfield',
      '#title' => t('Maryland self-employment expenses, high point'),
      '#default_value' => \Drupal::state()->get('surveynodeform_mdselfexpensehigh', 1000),
      '#size' => 5,
      '#maxlength' => 6,
      '#description' => t("Maryland self-employment expenses, high point."),
      '#required' => TRUE,
    );
    //New state: add settings
    //Georgia settings

    $form['surveynodeform_gaminwage'] = array(
      '#type' => 'textfield',
      '#title' => t('Georgia minimum wage'),
      '#default_value' => \Drupal::state()->get('surveynodeform_gaminwage', 8.25),
      '#size' => 5,
      '#maxlength' => 6,
      '#description' => t("Minimum wage in Georgia."),
      '#required' => TRUE,
    );
    $form['surveynodeform_gahrlow'] = array(
      '#type' => 'textfield',
      '#title' => t('Georgia hour range, low point'),
      '#default_value' => \Drupal::state()->get('surveynodeform_gahrlow', 1),
      '#size' => 5,
      '#maxlength' => 6,
      '#description' => t("Georgia hour range, low point."),
      '#required' => TRUE,
    );

    $form['surveynodeform_gahrhigh'] = array(
      '#type' => 'textfield',
      '#title' => t('Georgia hour range, high point'),
      '#default_value' => \Drupal::state()->get('surveynodeform_gahrhigh', 80),
      '#size' => 5,
      '#maxlength' => 6,
      '#description' => t("Georgia hour range, high point."),
      '#required' => TRUE,
    );

    $form['surveynodeform_gawagelow'] = array(
      '#type' => 'textfield',
      '#title' => t('Georgia wage range, low point'),
      '#default_value' => \Drupal::state()->get('surveynodeform_gawagelow', 1),
      '#size' => 5,
      '#maxlength' => 6,
      '#description' => t("Georgia wage range, low point."),
      '#required' => TRUE,
    );

    $form['surveynodeform_gawagehigh'] = array(
      '#type' => 'textfield',
      '#title' => t('Georgia wage range, high point'),
      '#default_value' => \Drupal::state()->get('surveynodeform_gawagehigh', 1000),
      '#size' => 5,
      '#maxlength' => 6,
      '#description' => t("Georgia wage range, high point."),
      '#required' => TRUE,
    );



    $form['surveynodeform_gaselfearnhigh'] = array(
      '#type' => 'textfield',
      '#title' => t('Georgia self-employment income, high point'),
      '#default_value' => \Drupal::state()->get('surveynodeform_gaselfearnhigh', 1000),
      '#size' => 5,
      '#maxlength' => 6,
      '#description' => t("Georgia self-employment income, high point."),
      '#required' => TRUE,
    );


    $form['surveynodeform_gaselfexpensehigh'] = array(
      '#type' => 'textfield',
      '#title' => t('Georgia self-employment expenses, high point'),
      '#default_value' => \Drupal::state()->get('surveynodeform_gaselfexpensehigh', 1000),
      '#size' => 5,
      '#maxlength' => 6,
      '#description' => t("Georgia self-employment expenses, high point."),
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }


}
