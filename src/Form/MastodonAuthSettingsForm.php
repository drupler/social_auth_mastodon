<?php

namespace Drupal\social_auth_mastodon\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\social_auth\Form\SocialAuthSettingsForm;
use Drupal\social_auth\Plugin\Network\NetworkInterface;

/**
 * Settings form for Social Auth Mastodon.
 */
class MastodonAuthSettingsForm extends SocialAuthSettingsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'social_auth_mastodon_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return array_merge(
      parent::getEditableConfigNames(),
      ['social_auth_mastodon.settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NetworkInterface $network = NULL): array {
    /** @var \Drupal\social_auth\Plugin\Network\NetworkInterface $network */
    $network = $this->networkManager->createInstance('social_auth_google');
    $form = parent::buildForm($form, $form_state, $network);

    $config = $this->config('social_auth_mastodon.settings');

    $form['network']['instance'] = [
      '#type' => 'textfield',
      '#required' => FALSE,
      '#title' => $this->t('Mastodon instance URI'),
      '#default_value' => $config->get('instance'),
      '#weight' => 4,
      '#description' => $this->t('The Mastodon instance that hosts accounts you want to log in with'),
    ];

    $form['network']['default_instance'] = [
      '#type' => 'checkbox',
      '#required' => FALSE,
      '#title' => $this->t('Make it a default Mastodon instance.'),
      '#description' => $this->t('If you enable this, users can not use any other instance to login.'),
      '#weight' => 5,
    ];

    $form['network']['#description'] = $this->t('You need to first create a Mastodon Development App at instance you are using for application development.');

    $form['network']['advanced']['#weight'] = 999;

    $form['network']['advanced']['scopes']['#description'] =
      $this->t('Define any additional scopes to be requested, separated by a comma (e.g.: follow,write:statuses,read:follows).<br>
                The scope \'read:accounts\' is added by default and always requested.<br>
                You can see the full list of valid scopes and their description <a href="@scopes">here</a>.', ['@scopes' => 'https://docs.joinmastodon.org/api/permissions/']
    );

    $form['network']['advanced']['endpoints']['#description'] =
      $this->t('Define the Endpoints to be requested when user authenticates with Mastodon for the first time<br>
                Enter each endpoint in different lines in the format <em>endpoint</em>|<em>name_of_endpoint</em>.<br>
                <b>For instance:</b><br>
                /api/v1/accounts/relationships|relationships<br>
                Look for the endpoints in the <a href="@api-docs">Mastodon REST API documentation</a><br>', ['@api-docs' => 'https://docs.joinmastodon.org/api/rest/accounts/']
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $this->config('social_auth_mastodon.settings')
      ->set('instance', trim($values['instance']))
      ->set('default_instance', trim($values['default_instance']))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
