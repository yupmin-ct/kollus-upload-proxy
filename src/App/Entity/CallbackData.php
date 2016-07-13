<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class CallbackData
 *
 * @package App\Entity
 * @ORM\Entity(repositoryClass="\App\Repository\CallbackDataRepository")
 * @ORM\Table(name="callbackDatas",indexes={@ORM\Index(name="oldUploadFileKey_idx", columns={"oldUploadFileKey"})})
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
    protected $oldUploadFileKey;

    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    protected $newUploadFileKey;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    protected $createdAt;

    /**
     * @var int
     * @ORM\Column(type="boolean")
     */
    protected $isDeleted = 0;

    /**
     * @var int
     * @ORM\Column(type="boolean")
     */
    protected $isError = 0;

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
    public function getIsDeleted()
    {
        return $this->isDeleted;
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
     * @param int $isDeleted
     */
    public function setIsDeleted($isDeleted)
    {
        $this->isDeleted = $isDeleted;
    }

    /**
     * @param int $isError
     */
    public function setIsError($isError)
    {
        $this->isError = $isError;
    }
}
