<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS
 * @copyright BINSHOPS
 *
 */

use Doctrine\ORM\EntityManagerInterface;
use PrestaShop\Module\ProductComment\Entity\ProductComment;
use PrestaShop\Module\ProductComment\Entity\ProductCommentCriterion;
use PrestaShop\Module\ProductComment\Entity\ProductCommentGrade;
use PrestaShop\Module\ProductComment\Repository\ProductCommentRepository;

require_once dirname(__FILE__) . '/../AbstractRESTController.php';

class BinshopsrestPostcommentModuleFrontController extends AbstractRESTController
{
    protected function processPostRequest()
    {
        $_POST = json_decode(Tools::file_get_contents('php://input'), true);

        if (!(int) $this->context->cookie->id_customer && !Configuration::get('PRODUCT_COMMENTS_ALLOW_GUESTS')) {

            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 205,
                'psdata' => 'user should login to leave a comment'
            ]));
            die;
        }

        $id_product = (int) Tools::getValue('id_product');
        $comment_title = Tools::getValue('comment_title');
        $comment_content = Tools::getValue('comment_content');
        $customer_name = Tools::getValue('customer_name');
        $criterions = Tools::getValue('criterion');

        /** @var ProductCommentRepository $productCommentRepository */
        $productCommentRepository = $this->context->controller->getContainer()->get('product_comment_repository');
        $isPostAllowed = $productCommentRepository->isPostAllowed(
            $id_product,
            (int) $this->context->cookie->id_customer,
            (int) $this->context->cookie->id_guest
        );
        if (!$isPostAllowed) {
            $this->ajaxRender(
                json_encode(
                    [
                        'success' => false,
                        'error' => $this->trans('You are not allowed to post a review at the moment, please try again later.', [], 'Modules.Productcomments.Shop'),
                    ]
                )
            );

            return false;
        }

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        //Create product comment
        $productComment = new ProductComment();
        $productComment
            ->setProductId($id_product)
            ->setTitle($comment_title)
            ->setContent($comment_content)
            ->setCustomerName($customer_name)
            ->setCustomerId($this->context->cookie->id_customer)
            ->setGuestId($this->context->cookie->id_guest)
            ->setDateAdd(new \DateTime('now', new \DateTimeZone('UTC')))
        ;
        $entityManager->persist($productComment);
        $this->addCommentGrades($productComment, $criterions);

        //Validate comment
        $errors = $this->validateComment($productComment);
        if (!empty($errors)) {
            $this->ajaxRender(
                json_encode(
                    [
                        'success' => false,
                        'errors' => $errors,
                    ]
                )
            );

            return false;
        }

        $entityManager->flush();

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => 200,
            'psdata' => $productComment->toArray()
        ]));
        die;
    }

    /**
     * @param ProductComment $productComment
     * @param array $criterions
     *
     * @throws Exception
     */
    private function addCommentGrades(ProductComment $productComment, array $criterions)
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $criterionRepository = $entityManager->getRepository(ProductCommentCriterion::class);
        $averageGrade = 0;

        foreach ($criterions as $criterionId => $grade) {
            $criterion = $criterionRepository->findOneBy(['id' => $criterionId]);
            $criterionGrade = new ProductCommentGrade(
                $productComment,
                $criterion,
                $grade
            );

            $entityManager->persist($criterionGrade);
            $averageGrade += $grade;
        }

        $averageGrade /= count($criterions);
        $productComment->setGrade($averageGrade);
    }

    /**
     * Manual validation for now, this would be nice to use Symfony validator with the annotation
     *
     * @param ProductComment $productComment
     *
     * @return array
     */
    private function validateComment(ProductComment $productComment)
    {
        $errors = [];
        if (empty($productComment->getTitle())) {
            $errors[] = $this->trans('Title cannot be empty', [], 'Modules.Productcomments.Shop');
        } elseif (strlen($productComment->getTitle()) > ProductComment::TITLE_MAX_LENGTH) {
            $errors[] = $this->trans('Title cannot be more than %s characters', [ProductComment::TITLE_MAX_LENGTH], 'Modules.Productcomments.Shop');
        }

        if (!$productComment->getCustomerId()) {
            if (empty($productComment->getCustomerName())) {
                $errors[] = $this->trans('Customer name cannot be empty', [], 'Modules.Productcomments.Shop');
            } elseif (strlen($productComment->getCustomerName()) > ProductComment::CUSTOMER_NAME_MAX_LENGTH) {
                $errors[] = $this->trans('Customer name cannot be more than %s characters', [ProductComment::CUSTOMER_NAME_MAX_LENGTH], 'Modules.Productcomments.Shop');
            }
        }

        return $errors;
    }
}
