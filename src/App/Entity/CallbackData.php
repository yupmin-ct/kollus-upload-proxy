<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class CallbackData
 *
 * @package App\Entity
 * @ORM\Entity(repositoryClass="\App\Repository\CallbackDataRepository")
 * @ORM\Table(
 *   name="callbackDatas",
 *   indexes={
 *     @ORM\Index(name="oldUploadFileKey_idx", columns={"serviceAccountKey","oldUploadFileKey"})
 *   },
 *   uniqueConstraints={
 *     @ORM\UniqueConstraint(name="newUploadFileKey_idx", columns={"newUploadFileKey"})
 *   }
 * )
 */
class CallbackData
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    protected $serviceAccountKey;

    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    protected $oldUploadFileKey;

    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    protected $newUploadFileKey;

    /**
     * @var int
     * @ORM\Column(type="boolean")
     */
    protected $willDeleted = 0;

    /**
     * @var int
     * @ORM\Column(type="boolean")
     */
    protected $isError = 0;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $errorMessage;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $errorData;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    protected $createdAt;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    protected $updatedAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getServiceAccountKey()
    {
        return $this->serviceAccountKey;
    }

    /**
     * @return string
     */
    public function getOldUploadFileKey()
    {
        return $this->oldUploadFileKey;
    }

    /**
     * @return string
     */
    public function getNewUploadFileKey()
    {
        return $this->newUploadFileKey;
    }

    /**
     * @return int
     */
    public function getWillDeleted()
    {
        return $this->willDeleted;
    }

    /**
     * @return int
     */
    public function getIsError()
    {
        return $this->isError;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @return string
     */
    public function getErrorData()
    {
        return unserialize($this->errorData);
    }

    /**
     * @return int
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return int
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param string $serviceAccountKey
     */
    public function setServiceAccountKey($serviceAccountKey)
    {
        $this->serviceAccountKey = $serviceAccountKey;
    }


    /**
     * @param string $oldUploadFileKey
     */
    public function setOldUploadFileKey($oldUploadFileKey)
    {
        $this->oldUploadFileKey = $oldUploadFileKey;
    }

    /**
     * @param string $newUploadFileKey
     */
    public function setNewUploadFileKey($newUploadFileKey)
    {
        $this->newUploadFileKey = $newUploadFileKey;
    }

    /**
     * @param int $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @param int $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @param int $willDeleted
     */
    public function setWillDeleted($willDeleted)
    {
        $this->willDeleted = $willDeleted;
    }

    /**
     * @param int $isError
     */
    public function setIsError($isError)
    {
        $this->isError = $isError;
    }

    /**
     * @param string $errorMessage
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;
    }

    /**
     * @param string $errorData
     */
    public function setErrorData($errorData)
    {
        $this->errorData = serialize($errorData);
    }
}
