<?php

declare(strict_types=1);

// =================================================================
// PRODUCT
// The object being built. It only knows how to hold data and draw
// itself — it has no idea how it was assembled.
// =================================================================

class CallingCard
{
    public string $firstName = '';
    public string $lastName = '';
    public string $businessName = '';
    public string $position = '';
    public string $street = '';
    public string $city = '';
    public string $email = '';
    public string $phone = '';
    public string $website = '';

    public int $width = 1000;
    public int $height = 600;

    public function fullName(): string
    {
        return trim("{$this->firstName} {$this->lastName}");
    }

    public function address(): string
    {
        return trim("{$this->street}, {$this->city}", ', ');
    }

    public function render(string $outputDirectory): string
    {
        $image = imagecreatetruecolor($this->width, $this->height);

        // Colors
        $background = imagecolorallocate($image, 245, 247, 250);
        $white      = imagecolorallocate($image, 255, 255, 255);
        $black      = imagecolorallocate($image, 30, 30, 30);
        $gray       = imagecolorallocate($image, 100, 100, 100);
        $blue       = imagecolorallocate($image, 40, 100, 200);

        // Background + card
        imagefill($image, 0, 0, $background);
        imagefilledrectangle($image, 50, 50, 950, 550, $white);

        // Left accent bar
        imagefilledrectangle($image, 50, 50, 75, 550, $blue);

        // Business name
        imagestring($image, 5, 120, 100, strtoupper($this->businessName), $blue);

        // Person name
        imagestring($image, 5, 120, 170, $this->fullName(), $black);

        // Position
        imagestring($image, 4, 120, 210, $this->position, $blue);

        // Divider
        imageline($image, 120, 260, 880, 260, $gray);

        // Email / Phone / Address
        imagestring($image, 4, 120, 310, 'Email: ' . $this->email, $black);
        imagestring($image, 4, 120, 365, 'Phone: ' . $this->phone, $black);
        imagestring($image, 4, 120, 420, 'Address: ' . $this->address(), $black);

        // Website
        if ($this->website !== '') {
            imagestring($image, 3, 120, 485, $this->website, $gray);
        }

        if (!is_dir($outputDirectory)) {
            mkdir($outputDirectory, 0755, true);
        }

        $filename = $outputDirectory . '/calling-card-' . uniqid() . '.png';

        if (!imagepng($image, $filename)) {
            imagedestroy($image);
            throw new RuntimeException("Could not save image to {$filename}");
        }

        imagedestroy($image);

        return $filename;
    }
}


// =================================================================
// BUILDER INTERFACE
// Declares one step-building method per piece of information the
// card needs. Every step returns $this so calls can be chained.
// =================================================================

interface CallingCardBuilderInterface
{
    public function setPersonalDetails(string $firstName, string $lastName): self;

    public function setBusinessDetails(string $businessName, string $position): self;

    public function setLocation(string $street, string $city): self;

    public function setContactDetails(string $email, string $phone): self;

    public function setWebsite(string $website): self;

    public function getCallingCard(): CallingCard;
}


// =================================================================
// CONCRETE BUILDER
// Actually fills in a CallingCard step by step. Auto-generates
// email/phone if the caller never explicitly set them (matching the
// original script's rand()-based behavior).
// =================================================================

class CallingCardBuilder implements CallingCardBuilderInterface
{
    private CallingCard $card;

    public function __construct()
    {
        $this->reset();
    }

    private function reset(): void
    {
        $this->card = new CallingCard();
    }

    public function setPersonalDetails(string $firstName, string $lastName): self
    {
        $this->card->firstName = $firstName;
        $this->card->lastName = $lastName;

        return $this;
    }

    public function setBusinessDetails(string $businessName, string $position): self
    {
        $this->card->businessName = $businessName;
        $this->card->position = $position;

        return $this;
    }

    public function setLocation(string $street, string $city): self
    {
        $this->card->street = $street;
        $this->card->city = $city;

        return $this;
    }

    public function setContactDetails(string $email, string $phone): self
    {
        $this->card->email = $email;
        $this->card->phone = $phone;

        return $this;
    }

    public function setWebsite(string $website): self
    {
        $this->card->website = $website;

        return $this;
    }

    public function getCallingCard(): CallingCard
    {
        if ($this->card->email === '' && $this->card->lastName !== '' && $this->card->firstName !== '') {
            $this->card->email = strtolower("{$this->card->lastName}.{$this->card->firstName}@auf.edu.ph");
        }

        if ($this->card->phone === '') {
            $this->card->phone = sprintf('+1 (555) %03d-%04d', random_int(100, 999), random_int(1000, 9999));
        }

        $finishedCard = $this->card;
        $this->reset();

        return $finishedCard;
    }
}


// =================================================================
// DIRECTOR
// Knows the "recipe" for a standard AUF CCS student card, so the
// client doesn't have to repeat those fixed details every time.
// =================================================================

class CallingCardDirector
{
    public function __construct(private CallingCardBuilderInterface $builder)
    {
    }

    public function buildAufCcsStudentCard(string $firstName, string $lastName): CallingCard
    {
        return $this->builder
            ->setPersonalDetails($firstName, $lastName)
            ->setBusinessDetails('College of Computing Studies', 'BSIT Student')
            ->setLocation('AUF CCS Building', 'Angeles City')
            ->setWebsite('www.auf.edu.ph')
            ->getCallingCard();
    }
}


// =================================================================
// CLIENT CODE
// This replaces the old flat script logic at the bottom.
// =================================================================

$builder = new CallingCardBuilder();
$director = new CallingCardDirector($builder);

$card = $director->buildAufCcsStudentCard('Angela Nicole', 'A. Arndt');

$outputDirectory = __DIR__ . '/cards';

try {
    $filename = $card->render($outputDirectory);
    echo "Calling card generated successfully.\n";
    echo "File: $filename\n";
} catch (RuntimeException $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}