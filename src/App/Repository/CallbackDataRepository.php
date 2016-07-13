<?php

namespace App\Repository;

use App\Entity\CallbackData;
use Doctrine\ORM\EntityManager;

class CallbackDataRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param string $oldUploadFileKey
     * @return CallbackData
     */
    public function findByoldUploadFileKey($oldUploadFileKey) {
        /**
         * @var EntityManager $entityManager
         */
        $entityManager = $this->getEntityManager();

        $dql = "SELECT c FROM \App\Entity\CallbackData c ".
            "WHERE c.isDeleted = 0 AND (c.isError = 0 OR c.oldUploadFileKey = ?1)".
            "ORDER BY c.id DESC";

        $query = $entityManager
            ->createQuery($dql)
            ->setParameter(1, $oldUploadFileKey);

        return $query->getFirstResult();
    }

    /**
     * @param string $oldUploadFileKey
     * @param string $newUploadFileKey
     * @return CallbackData
     */
    public function registerBy($oldUploadFileKey, $newUploadFileKey) {
        /**
         * @var EntityManager $entityManager
         */
        $entityManager = $this->getEntityManager();

        /**
         * @var CallbackData $callbackData
         */

        $callbackData = $this->findByoldUploadFileKey($oldUploadFileKey);
//        $callbackData = $this->findOneBy(['oldUploadFileKey' => $oldUploadFileKey]);

        if (empty($callbackData)) {
            $callbackData = new \App\Entity\CallbackData();
            $callbackData->setOldUploadFileKey($oldUploadFileKey);
            $callbackData->setNewUploadFileKey($newUploadFileKey);
            $callbackData->setCreatedAt(time());

            $this->getEntityManager()->persist($callbackData);
        } else {
            $callbackData->setNewUploadFileKey($newUploadFileKey);
            $callbackData->setIsDeleted(0);
            $callbackData->setIsError(0);
            $callbackData->setCreatedAt(time());
        }

        $entityManager->flush();

        return $callbackData;
    }

    /**
     * @param int $afterSeconds
     * @return CallbackData[]
     */
    public function findAllAfterSeconds($afterSeconds) {
        /**
         * @var EntityManager $entityManager
         */
        $entityManager = $this->getEntityManager();

        $dql = "SELECT c FROM \App\Entity\CallbackData c ".
            "WHERE c.isDeleted = 1 AND c.isError = 0 AND c.createdAt < ?1 ".
            "ORDER BY c.id DESC";

        $query = $entityManager
            ->createQuery($dql)
            ->setParameter(1, (time() - $afterSeconds));

        return $query->getResult();
    }

    /**
     * @param CallbackData $callbackData
     */
    public function removeBy($callbackData) {
        /**
         * @var EntityManager $entityManager
         */
        $entityManager = $this->getEntityManager();

        // remove callbackData
        $entityManager->remove($callbackData);
        $entityManager->flush();
    }

    /**
     * @param string $newUploadFileKey
     */
    public function setIsDeletedByNewUploadFileKey($newUploadFileKey) {
        /**
         * @var EntityManager $entityManager
         */
        $entityManager = $this->getEntityManager();

        /**
         * @var CallbackData $callbackData
         */
        $callbackData = $this->findOneBy([
            'newUploadFileKey' => $newUploadFileKey,
            'isDeleted' => 0,
            'isError' => 0,
        ]);

        if (!empty($callbackData)) {
            $callbackData->setIsDeleted(1);
            $entityManager->flush();
        }
    }

    /**
     * @param CallbackData $callbackData
     */
    public function setIsErrorBy($callbackData) {
        /**
         * @var EntityManager $entityManager
         */
        $entityManager = $this->getEntityManager();

        $callbackData->setIsError(1);
        $entityManager->flush();
    }
}
