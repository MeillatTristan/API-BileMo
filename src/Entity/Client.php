<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation as Serializer;


/**
 * @ORM\Entity(repositoryClass=ClientRepository::class)
 * @Hateoas\Relation(
 *      "self", 
 *      href = "expr('/api/users/' ~ object.getId() ~ '/show') ",
 *      exclusion = @Hateoas\Exclusion(groups={"UserShow"}))
 * 
 * @Hateoas\Relation(
 *      "modify", 
 *      href = "expr('/api/users/' ~ object.getId() ~ '/put') ",
 *      exclusion = @Hateoas\Exclusion(groups={"UserShow"}))
 * 
 * @Hateoas\Relation(
 *      "delete", 
 *      href = "expr('/api/users/' ~ object.getId() ~ '/delete') ",
 *      exclusion = @Hateoas\Exclusion(groups={"UserShow"}))
 */
class Client
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups("clientShow")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("clientShow")
     * @Assert\NotBlank(message="Ce champ est obligatoire")
     * @Serializer\Groups({"ClientShow", "UserShow"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("clientShow")
     * @Assert\NotBlank(message="Ce champ est obligatoire")
     * @Serializer\Groups({"ClientShow", "UserShow"})
     */
    private $email;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="Client")
     * @ORM\JoinColumn(nullable=false)
     * @Groups("clientShow")
     * @Serializer\Groups({"ClientShow", "UserShow"})
     */
    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
