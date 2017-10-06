<?php
/**
 * @file
 * contains Drupal\scripps_node\Form
 */

namespace Drupal\scripps_node\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ThankYouForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'scripps_node.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scripps_node_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('scripps_node.adminsettings');

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('The title of the page.'),
      '#default_value' => $config->get('title')
    ];

    $form['thank_you_text'] = [
      '#type' => 'text_format',
      '#format'=> 'full_html',
      '#title' => $this->t('Thank You Message'),
      '#description' => $this->t('Thank you message to display after a user submits a device.'),
      '#default_value' => $config->get('thank_you_text')['value']
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('scripps_node.adminsettings')
      ->set('title', $form_state->getValue('title'))
      ->set('thank_you_text', $form_state->getValue('thank_you_text'))
      ->save();
  }
}