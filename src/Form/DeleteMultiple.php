<?php

/**
 * @file
 * Contains \Drupal\node\Form\DeleteMultiple.
 */

namespace Drupal\profile\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a node deletion confirmation form.
 */
class DeleteMultiple extends ConfirmFormBase {

  /**
   * The array of nodes to delete.
   *
   * @var array
   */
  protected $profiles = array();

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $manager;

  /**
   * Constructs a DeleteMultiple form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityManagerInterface $manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->storage = $manager->getStorage('profile');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'profile_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return format_plural(count($this->profiles), 'Are you sure you want to delete this profile?', 'Are you sure you want to delete these profiles?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('profile.overview_profiles');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->profiles = $this->tempStoreFactory->get('profile_multiple_delete_confirm')->get(\Drupal::currentUser()->id());
    if (empty($this->profiles)) {
      return new RedirectResponse(url('admin/config/people/profiles', array('absolute' => TRUE)));
    }

    $form['profiles'] = array(
      '#theme' => 'item_list',
      '#items' => array_map(function ($profile) {
        return String::checkPlain($profile->label());
      }, $this->profiles),
    );
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->profiles)) {
      $this->storage->delete($this->profiles);
      $this->tempStoreFactory->get('profile_multiple_delete_confirm')->delete(\Drupal::currentUser()->id());
      $count = count($this->profiles);
      $this->logger('content')->notice('Deleted @count profiles.', array('@count' => $count));
      drupal_set_message(format_plural($count, 'Deleted 1 profile.', 'Deleted @count profiles.'));
    }
    $form_state->setRedirect('profile.overview_profiles');
  }

}
