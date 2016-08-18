<?php

namespace App\Repository;

use App\Entity\CallbackData;
use Doctrine\ORM\EntityManager;

class CallbackDataRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param string $serviceAccountKey
     * @param int $page
     * @param int $perPage
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function findPaginatorByPage($serviceAccountKey, $page = 1, $perPage = 10)
    {
        /**
         * @var EntityManager $entityManager
         */
        $entityManager = $this->getEntityManager();

        $page = empty($page) ? 1 : $page;

        $dql = "SELECT c FROM \App\Entity\CallbackData c WHERE c.serviceAccountKey = ?1 ORDER BY c.id DESC";
        $query = $entityManager->createQuery($dql)
            ->setFirstResult($perPage * ($page - 1))
            ->setMaxResults($perPage)
            ->setParameter(1, $serviceAccountKey);

        return new \Doctrine\ORM\Tools\Pagination\Paginator($query, $fetchJoinCollection = true);
    }

    /**
     * @param string $serviceAccountKey
     * @param string $oldUploadFileKey
     * @return CallbackData
     */
    public function findByOldUploadFileKey($serviceAccountKey, $oldUploadFileKey)
    {
        /**
         * @var EntityManager $entityManager
         */
        $entityManager = $this->getEntityManager();

        $dql = "SELECT c FROM \App\Entity\CallbackData c ".
            "WHERE (c.willDeleted = 0 OR c.isError = 0) AND c.oldUploadFileKey = ?1 AND c.serviceAccountKey = ?2".
            "ORDER BY c.id DESC";

        $query = $entityManager
            ->createQuery($dql)
            ->setParameter(1, $oldUploadFileKey)
            ->setParameter(2, $serviceAccountKey);

        return $query->getFirstResult();
    }

    /**
     * @param string $serviceAccountKey
     * @param string $oldUploadFileKey
     * @param string $newUploadFileKey
     * @return CallbackData
     */
    public function registerBy($serviceAccountKey, $oldUploadFileKey, $newUploadFileKey)
    {
        /**
         * @var EntityManager $entityManager
         */
        $entityManager = $this->getEntityManager();

        /**
         * @var CallbackData $callbackData
         */

        $callbackData = $this->findByOldUploadFileKey($serviceAccountKey, $oldUploadFileKey);
        if (empty($callbackData)) {
            $callbackData = new \App\Entity\CallbackData();
            $callbackData->setServiceAccountKey($serviceAccountKey);
            $callbackData->setOldUploadFileKey($oldUploadFileKey);
            $callbackData->setNewUploadFileKey($newUploadFileKey);
            $callbackData->setCreatedAt(time());
            $callbackData->setUpdatedAt(time());

            $this->getEntityManager()->persist($callbackData);
        } else {
            $callbackData->setNewUploadFileKey($newUploadFileKey);
            $callbackData->setWillDeleted(0);
            $callbackData->setIsError(0);
            $callbackData->setCreatedAt(time());
            $callbackData->setUpdatedAt(time());
        }

        $entityManager->flush();

        return $callbackData;
    }

    /**
     * @param string $serviceAccountKey
     * @param int $afterSeconds
     * @return CallbackData[]
     */
    public function findAllAfterSeconds($serviceAccountKey, $afterSeconds)
    {
        /**
         * @var EntityManager $entityManager
         */
        $entityManager = $this->getEntityManager();

        $dql = "SELECT c FROM \App\Entity\CallbackData c ".
            "WHERE c.willDeleted = 1 AND c.isError = 0 AND c.createdAt < ?1 AND c.serviceAccountKey = ?2".
            "ORDER BY c.id DESC";

        $query = $entityManager
            ->createQuery($dql)
            ->setParameter(1, (time() - $afterSeconds))
            ->setParameter(2, $serviceAccountKey);

        return $query->getResult();
    }

    /**
     * @param CallbackData $callbackData
     */
    public function removeBy($callbackData)
    {
        /**
         * @var EntityManager $entityManager
         */
        $entityManager = $this->getEntityManager();

        // remove callbackData
        $entityManager->remove($callbackData);
        $entityManager->flush();
    }

    /**
     * @param CallbackData $callbackData
     */
    public function setWillDeletedBy($callbackData)
    {
        /**
         * @var EntityManager $entityManager
         */
        $entityManager = $this->getEntityManager();

        $callbackData->setWillDeleted(1);
        $callbackData->setUpdatedAt(time());
        $entityManager->flush();
    }

    /**
     * @param CallbackData $callbackData
     * @param string $errorMessage
     * @param string $errorData
     */
    public function setIsErrorBy($callbackData, $errorMessage, $errorData)
    {
        /**
         * @var EntityManager $entityManager
         */
        $entityManager = $this->getEntityManager();

        $callbackData->setIsError(1);
        $callbackData->setErrorMessage($errorMessage);
        $callbackData->setErrorData($errorData);
        $callbackData->setUpdatedAt(time());
        $entityManager->flush();
    }

    /**
     * @param CallbackData $callbackData
     */
    public function deleteBy($callbackData)
    {
        /**
         * @var EntityManager $entityManager
         */
        $entityManager = $this->getEntityManager();

        $entityManager->remove($callbackData);
        $entityManager->flush();
    }

    /**
     * @param CallbackData $callbackData
     */
    public function resetBy($callbackData)
    {
        /**
         * @var EntityManager $entityManager
         */
        $entityManager = $this->getEntityManager();

        $callbackData->setWillDeleted(0);
        $callbackData->setIsError(0);
        $callbackData->setErrorData('');
        $callbackData->setErrorMessage('');
        $callbackData->setUpdatedAt(time());
        $entityManager->flush();
    }
}
