<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\AdminBundle\Action;

use Sonata\AdminBundle\Admin\AdminHelper;
use Sonata\AdminBundle\Request\AdminFetcherInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

final class AppendFormFieldElementAction
{
    /**
     * @var AdminHelper
     */
    private $helper;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var AdminFetcherInterface
     */
    private $adminFetcher;

    public function __construct(Environment $twig, AdminFetcherInterface $adminFetcher, AdminHelper $helper)
    {
        $this->helper = $helper;
        $this->twig = $twig;
        $this->adminFetcher = $adminFetcher;
    }

    /**
     * @throws NotFoundHttpException
     */
    public function __invoke(Request $request): Response
    {
        try {
            $admin = $this->adminFetcher->get($request);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundHttpException(sprintf(
                'Could not find admin for code "%s".',
                $request->get('_sonata_admin')
            ));
        }

        $objectId = $request->get('objectId');
        if (null !== $objectId) {
            $subject = $admin->getObject($objectId);
            if (null === $subject) {
                throw new NotFoundHttpException(sprintf('Could not find subject for id "%s"', $objectId));
            }
        } else {
            $subject = $admin->getNewInstance();
        }

        $admin->setSubject($subject);

        $elementId = $request->get('elementId');
        [, $form] = $this->helper->appendFormFieldElement($admin, $subject, $elementId);

        $view = $this->helper->getChildFormView($form->createView(), $elementId);
        \assert(null !== $view);

        // render the widget
        $renderer = $this->twig->getRuntime(FormRenderer::class);
        $renderer->setTheme($view, $admin->getFormTheme());

        return new Response($renderer->searchAndRenderBlock($view, 'widget'));
    }
}
