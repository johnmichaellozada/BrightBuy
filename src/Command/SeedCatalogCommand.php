<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:seed-catalog',
    description: 'Populate BrightBuy products and category images'
)]
class SeedCatalogCommand extends Command
{
    public function __construct(
        private readonly Connection $connection
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $categories = [
            'Electronics' => [
                'Gadgets and electronic accessories',
                'images/categories/electronics.png',
            ],
            'Toys' => [
                'Toys, games, and play products',
                'images/categories/toys.png',
            ],
            'Fashion' => [
                'Clothing, footwear, and accessories',
                'images/categories/fashion.png',
            ],
            'Home & Living' => [
                'Useful products for home and everyday living',
                'images/categories/home.png',
            ],
            'Sports & Outdoors' => [
                'Sports, exercise, and outdoor equipment',
                'images/categories/sports.png',
            ],
            'Beauty' => [
                'Beauty and personal care products',
                'images/categories/beauty.png',
            ],
        ];

        foreach ($categories as $name => [$description, $image]) {
            $this->connection->executeStatement(
                'UPDATE category
                 SET description = ?, image = ?
                 WHERE name = ?',
                [$description, $image, $name]
            );

            $exists = $this->connection->fetchOne(
                'SELECT id FROM category WHERE name = ?',
                [$name]
            );

            if ($exists === false) {
                $this->connection->insert('category', [
                    'name' => $name,
                    'description' => $description,
                    'image' => $image,
                ]);
            }
        }

        $categoryIds = [];
        foreach ($this->connection->fetchAllAssociative(
            'SELECT id, name FROM category'
        ) as $category) {
            $categoryIds[$category['name']] = (int) $category['id'];
        }

        $products = [
            1 => ['Everyday Backpack', 'Fashion', 'A practical backpack for everyday use.', 899],
            2 => ['Wireless Earbuds', 'Electronics', 'Compact wireless earbuds for listening on the go.', 799],
            3 => ['White Sneakers', 'Fashion', 'Casual white sneakers for everyday wear.', 1299],
            4 => ['Smartwatch', 'Electronics', 'A smartwatch for daily activity and notifications.', 1599],
            5 => ['Water Bottle', 'Sports & Outdoors', 'A reusable bottle for hydration.', 299],
            6 => ['Bluetooth Speaker', 'Electronics', 'A portable speaker with wireless connectivity.', 999],
            7 => ['Wireless Charger', 'Electronics', 'A convenient wireless charging accessory.', 599],
            8 => ['RGB Keyboard', 'Electronics', 'A keyboard with colorful RGB lighting.', 1499],
            9 => ['Casual Hoodie', 'Fashion', 'A comfortable hoodie for casual outfits.', 799],
            10 => ['Denim Jacket', 'Fashion', 'A classic denim jacket.', 1199],
            11 => ['Travel Backpack', 'Fashion', 'A backpack suitable for travel and daily essentials.', 999],
            12 => ['Desk Lamp', 'Home & Living', 'A lamp for a desk or study area.', 499],
            13 => ['Coffee Mug', 'Home & Living', 'A mug for coffee, tea, and other drinks.', 199],
            14 => ['Decorative Cushion', 'Home & Living', 'A cushion for home comfort and decoration.', 399],
            15 => ['Storage Organizer', 'Home & Living', 'An organizer for household items.', 349],
            16 => ['Building Blocks', 'Toys', 'Building blocks for creative play.', 499],
            17 => ['Remote-Control Car', 'Toys', 'A toy car designed for remote-controlled play.', 899],
            18 => ['Teddy Bear', 'Toys', 'A soft teddy bear plush toy.', 399],
            19 => ['Jigsaw Puzzle', 'Toys', 'A puzzle for entertainment and problem-solving.', 249],
            20 => ['Basketball Hoop', 'Sports & Outdoors', 'A basketball hoop for recreational practice.', 1999],
            21 => ['Facial Care Device', 'Beauty', 'A personal-care device for a beauty routine.', 899],
            22 => ['Skincare Product', 'Beauty', 'A skincare item for personal care.', 399],
            23 => ['Hair Dryer', 'Beauty', 'A hair dryer for everyday hair styling.', 999],
            24 => ['Perfume', 'Beauty', 'A fragrance product for personal use.', 699],
            25 => ['Makeup Product', 'Beauty', 'A makeup item for a beauty routine.', 299],
            26 => ['Yoga Mat', 'Sports & Outdoors', 'A mat for yoga and floor exercises.', 599],
            27 => ['Dumbbells', 'Sports & Outdoors', 'Weights for strength training.', 799],
            28 => ['Shaker Bottle', 'Sports & Outdoors', 'A shaker bottle for mixing drinks.', 249],
            29 => ['Sports Cap', 'Fashion', 'A cap for casual wear and outdoor activities.', 349],
        ];

        $created = 0;
        $updated = 0;

        foreach ($products as $number => [$name, $categoryName, $description, $price]) {
            $image = sprintf('images/products/product%d.png', $number);
            $categoryId = $categoryIds[$categoryName] ?? null;

            if ($categoryId === null) {
                $output->writeln(
                    '<error>Missing category: '.$categoryName.'</error>'
                );
                return Command::FAILURE;
            }

            $existing = $this->connection->fetchAssociative(
                'SELECT id, name FROM product WHERE image = ?',
                [$image]
            );

            if ($existing !== false) {
                $this->connection->update('product', [
                    'name' => $name,
                    'description' => $description,
                    'category_id' => $categoryId,
                ], [
                    'id' => $existing['id'],
                ]);

                $updated++;
                continue;
            }

            // Preserve the existing Wireless Mouse and Lambo records.
            // Do not replace an existing product with the same name.
            $sameName = $this->connection->fetchOne(
                'SELECT id FROM product WHERE name = ?',
                [$name]
            );

            if ($sameName !== false) {
                $this->connection->update('product', [
                    'image' => $image,
                    'description' => $description,
                    'category_id' => $categoryId,
                ], [
                    'id' => $sameName,
                ]);

                $updated++;
                continue;
            }

            $this->connection->insert('product', [
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'stock' => 0,
                'category_id' => $categoryId,
                'image' => $image,
            ]);

            $created++;
        }

        $output->writeln('<info>Catalog population completed.</info>');
        $output->writeln('New products: '.$created);
        $output->writeln('Existing image-linked products updated: '.$updated);
        $output->writeln('New product stock defaults to 0; existing stock is preserved.');

        return Command::SUCCESS;
    }
}
