<?php

declare(strict_types=1);

// =================================================================
// PROTOTYPE INTERFACE
// Anything that wants to be "cloneable" via this pattern exposes a
// cloneCard() method that returns a fully independent copy of itself.
// =================================================================

interface CardPrototypeInterface
{
    public function cloneCard(): self;
}


// =================================================================
// PRODUCT / PROTOTYPE
// Same CallingCard as before, but now implements cloneCard().
// Since every property here is a plain string/int, PHP's built-in
// `clone` already gives us an independent copy — no need to override
// __clone().
// =================================================================

class CallingCard implements CardPrototypeInterface
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

    public function cloneCard(): self
    {
        return clone $this;
    }

    public function regenerateEmail(): void
    {
        $this->email = strtolower("{$this->lastName}.{$this->firstName}@auf.edu.ph");
    }

    public function regeneratePhone(): void
    {
        $this->phone = sprintf('+1 (555) %03d-%04d', random_int(100, 999), random_int(1000, 9999));
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

        $slug = strtolower($this->firstName . '-' . $this->lastName);
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);

        $filename = $outputDirectory . '/calling-card-' . $slug . '.png';

        if (!imagepng($image, $filename)) {
            imagedestroy($image);
            throw new RuntimeException("Could not save image to {$filename}");
        }

        imagedestroy($image);

        return $filename;
    }
}


// =================================================================
// CLIENT CODE
// Build ONE prototype card with everything shared by every student,
// then clone it once per student, only changing what's different.
// =================================================================

$students = [
    ['first_name' => 'Felicity',  'last_name' => 'Hampton'],
    ['first_name' => 'Hank',      'last_name' => 'Rice'],
    ['first_name' => 'Ada',       'last_name' => 'Wilson'],
    ['first_name' => 'Daniel',    'last_name' => 'Salgado'],
    ['first_name' => 'Avalynn',   'last_name' => 'Crane'],
    ['first_name' => 'Fox',       'last_name' => 'Summers'],
    ['first_name' => 'Frankie',   'last_name' => 'Andersen'],
    ['first_name' => 'Alistair',  'last_name' => 'Decker'],
    ['first_name' => 'Aleena',    'last_name' => 'Phillips'],
    ['first_name' => 'Andrew',    'last_name' => 'Marks'],
    ['first_name' => 'Monica',    'last_name' => 'French'],
    ['first_name' => 'Corey',     'last_name' => 'Hess'],
    ['first_name' => 'Kaliyah',   'last_name' => 'Richard'],
    ['first_name' => 'Ahmed',     'last_name' => 'Richardson'],
    ['first_name' => 'Allison',   'last_name' => 'Cortes'],
    ['first_name' => 'Banks',     'last_name' => 'McGee'],
    ['first_name' => 'Kayleigh',  'last_name' => 'Mendoza'],
    ['first_name' => 'Dominic',   'last_name' => 'Atkins'],
    ['first_name' => 'Mina',      'last_name' => 'Beasley'],
    ['first_name' => 'Stanley',   'last_name' => 'Jefferson'],
];

$prototype = new CallingCard();
$prototype->businessName = 'College of Computing Studies';
$prototype->position = 'BSIT Student';
$prototype->street = 'AUF CCS Building';
$prototype->city = 'Angeles City';
$prototype->website = 'www.auf.edu.ph';

$outputDirectory = __DIR__ . '/cards';

$generated = 0;

foreach ($students as $student) {
    $card = $prototype->cloneCard();

    $card->firstName = $student['first_name'];
    $card->lastName = $student['last_name'];
    $card->regenerateEmail();
    $card->regeneratePhone();

    try {
        $filename = $card->render($outputDirectory);
        echo "Generated: {$card->fullName()} -> $filename\n";
        $generated++;
    } catch (RuntimeException $e) {
        echo "ERROR generating card for {$card->fullName()}: " . $e->getMessage() . "\n";
    }
}

echo "\nDone. $generated / " . count($students) . " calling cards generated in $outputDirectory\n";