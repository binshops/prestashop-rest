<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS
 * @copyright BINSHOPS
 *
 */

require_once dirname(__FILE__) . '/../AbstractRESTController.php';

class BinshopsrestListcommentsModuleFrontController extends AbstractRESTController
{
    protected function processGetRequest()
    {
        $idProduct = (int) Tools::getValue('id_product');
        $page = (int) Tools::getValue('page', 1);
        $isLastNameAnynomus = Configuration::get('PRODUCT_COMMENTS_ANONYMISATION');
        /** @var ProductCommentRepository $productCommentRepository */
        $productCommentRepository = $this->context->controller->getContainer()->get('product_comment_repository');

        $productComments = $productCommentRepository->paginate(
            $idProduct,
            $page,
            (int) Configuration::get('PRODUCT_COMMENTS_COMMENTS_PER_PAGE'),
            (bool) Configuration::get('PRODUCT_COMMENTS_MODERATE')
        );
        $productCommentsNb = $productCommentRepository->getCommentsNumber(
            $idProduct,
            (bool) Configuration::get('PRODUCT_COMMENTS_MODERATE')
        );

        $responseArray = [
            'comments_nb' => $productCommentsNb,
            'comments_per_page' => Configuration::get('PRODUCT_COMMENTS_COMMENTS_PER_PAGE'),
            'comments' => [],
        ];

        foreach ($productComments as $productComment) {
            $dateAdd = new \DateTime($productComment['date_add'], new \DateTimeZone('UTC'));
            $dateAdd->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            $dateFormatter = new \IntlDateFormatter(
                $this->context->language->locale,
                \IntlDateFormatter::SHORT,
                \IntlDateFormatter::SHORT
            );
            $productComment['customer_name'] = htmlentities($productComment['customer_name']);
            $productComment['title'] = htmlentities($productComment['title']);
            $productComment['content'] = htmlentities($productComment['content']);
            $productComment['date_add'] = $dateFormatter->format($dateAdd);

            if ($isLastNameAnynomus) {
                $productComment['lastname'] = substr($productComment['lastname'], 0, 1) . '.';
            }

            $usefulness = $productCommentRepository->getProductCommentUsefulness($productComment['id_product_comment']);
            $productComment = array_merge($productComment, $usefulness);
            if (empty($productComment['customer_name']) && !isset($productComment['firstname']) && !isset($productComment['lastname'])) {
                $productComment['customer_name'] = $this->trans('Deleted account', [], 'Modules.Productcomments.Shop');
            }

            $responseArray['comments'][] = $productComment;
        }

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => 200,
            'psdata' => $responseArray
        ]));
        die;
    }
}
