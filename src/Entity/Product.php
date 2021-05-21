<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation as Serializer;


/**
 * @ORM\Entity(repositoryClass=ProductRepository::class)
 * @Serializer\XmlRoot("product")
 * @Hateoas\Relation(
 *      "self", 
 *      href = "expr('/api/products/' ~ object.getId() ~ '/show') ",
 *      exclusion = @Hateoas\Exclusion(groups={"default"}))
 * 
 * @Hateoas\Relation(
 *      "modify", 
 *      href = "expr('/api/products/' ~ object.getId() ~ '/put') ",
 *      exclusion = @Hateoas\Exclusion(groups={"default"}))
 * 
 * @Hateoas\Relation(
 *      "delete", 
 *      href = "expr('/api/products/' ~ object.getId() ~ '/delete') ",
 *      exclusion = @Hateoas\Exclusion(groups={"default"}))
 */
class Product
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Serializer\XmlAttribute
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="Ce champ est obligatoire")
     * @Serializer\Groups({"default"})
     */
    private $model;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="Ce champ est obligatoire")
     * @Serializer\Groups({"default"})
     */
    private $price;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="Ce champ est obligatoire")
     * @Serializer\Groups({"default"})
     */
    private $brand;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): self
    {
        $this->brand = $brand;

        return $this;
    }
}
