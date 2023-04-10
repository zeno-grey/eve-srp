<?php

declare(strict_types=1);

namespace EveSrp\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="modifiers", options={"charset"="utf8mb4", "collate"="utf8mb4_unicode_520_ci"})
 */
class Modifier
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?\DateTime $created = null;

    /**
     * relative or absolute
     *
     * @ORM\Column(type="string", name="mod_type", length=8)
     * @see Type
     */
    private ?string $modType = null;

    /**
     * @ORM\ManyToOne(targetEntity="Request", inversedBy="modifiers")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Request $request = null;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?User $user = null;

    /**
     * @ORM\Column(type="text", length=16777215, nullable=true)
     */
    private ?string $note = null;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="voided_user_id")
     */
    private ?User $voidedUser = null;

    /**
     * @ORM\Column(type="datetime", name="voided_time", nullable=true)
     */
    private ?\DateTime $voidedTime = null;

    /**
     * @ORM\Column(type="bigint", name="mod_value")
     */
    private ?int $modValue = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /** @noinspection PhpUnused */
    public function getCreated(): ?\DateTime
    {
        return $this->created;
    }

    /** @noinspection PhpUnused */
    public function getModType(): ?string
    {
        return $this->modType;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    /** @noinspection PhpUnused */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /** @noinspection PhpUnused */
    public function getNote(): ?string
    {
        return $this->note;
    }

    /** @noinspection PhpUnused */
    public function getVoidedUser(): ?User
    {
        return $this->voidedUser;
    }

    /** @noinspection PhpUnused */
    public function getVoidedTime(): ?\DateTime
    {
        return $this->voidedTime;
    }

    /** @noinspection PhpUnused */
    public function getModValue(): ?int
    {
        return $this->modValue;
    }
}
