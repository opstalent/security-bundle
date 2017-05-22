<?php

namespace Opstalent\SecurityBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class AclSubscriber implements EventSubscriberInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var array
     */
    private $acl;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT => 'verifyAcl',
        ];
    }

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param array $acl
     */
    public function __construct(TokenStorageInterface $tokenStorage, array $acl)
    {
        $this->tokenStorage = $tokenStorage;
        $this->acl = $acl;
    }

    /**
     * @param FormEvent $event
     * @throws AccessDeniedException
     */
    public function verifyAcl(FormEvent $event)
    {
        $data = $event->getData();
        $roles = $this->tokenStorage->getToken()->getRoles();

        foreach ($data as $key => $value) {
            if (!$this->isFieldAllowed($key, $roles)) {
                throw new AccessDeniedException('Access denied');
            }
        }
    }

    /**
     * @param string $field
     * @param array $roles
     * @return bool
     */
    protected function isFieldAllowed($field, array $roles) : bool
    {
        foreach ($roles as $role) {
            if (!array_key_exists($role->getRole(), $this->acl)) {
                continue;
            }

            if (false !== array_search($field, $this->acl[$role->getRole()])) {
                return true;
            }
        }

        return false;
    }
}
