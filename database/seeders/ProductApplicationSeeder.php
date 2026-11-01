<?php

namespace Database\Seeders;

use App\Support\MediaUploadRules;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ProductApplicationSeeder extends Seeder
{
    /**
     * The twelve Product Applications approved on the Product Applications map.
     *
     * `x`/`y` are the circle centres as percentages of the 1920x1080
     * artwork, measured from the supplied composite by radial ring
     * detection. They are seed values, not gospel: marketing nudges them in
     * the admin. Every row starts with no Application video, which keeps the
     * circle visible but inert until marketing uploads one.
     *
     * `anchor_x`/`anchor_y` are where each leader line ends on the artwork —
     * traced along the orange lines of the composite, which land on the small
     * dots already drawn in the clean artwork.
     *
     * @var list<array{key: string, label: string, x: float, y: float, anchor_x: float, anchor_y: float}>
     */
    public const ROWS = [
        ['key' => 'product-application-01', 'label' => 'Purlins', 'x' => 25.75, 'y' => 14.85, 'anchor_x' => 41.56, 'anchor_y' => 43.43],
        ['key' => 'product-application-02', 'label' => 'Flat Bar', 'x' => 43.79, 'y' => 8.94, 'anchor_x' => 45.10, 'anchor_y' => 41.20],
        ['key' => 'product-application-03', 'label' => 'Steel Plate', 'x' => 59.99, 'y' => 8.31, 'anchor_x' => 56.15, 'anchor_y' => 21.11],
        ['key' => 'product-application-04', 'label' => 'Angle Bar', 'x' => 73.21, 'y' => 18.43, 'anchor_x' => 66.15, 'anchor_y' => 26.76],
        ['key' => 'product-application-05', 'label' => 'Steel Pipes', 'x' => 16.00, 'y' => 31.95, 'anchor_x' => 29.64, 'anchor_y' => 34.72],
        ['key' => 'product-application-06', 'label' => 'Channel Bar', 'x' => 82.00, 'y' => 38.18, 'anchor_x' => 76.20, 'anchor_y' => 42.22],
        ['key' => 'product-application-07', 'label' => 'Tubulars', 'x' => 16.00, 'y' => 55.28, 'anchor_x' => 32.14, 'anchor_y' => 53.43],
        ['key' => 'product-application-08', 'label' => 'Sheet Pile', 'x' => 80.00, 'y' => 62.46, 'anchor_x' => 72.34, 'anchor_y' => 61.48],
        ['key' => 'product-application-09', 'label' => 'Wide Flange', 'x' => 18.37, 'y' => 73.32, 'anchor_x' => 31.72, 'anchor_y' => 65.56],
        ['key' => 'product-application-10', 'label' => 'Deformed Bar', 'x' => 68.83, 'y' => 75.75, 'anchor_x' => 61.30, 'anchor_y' => 68.52],
        ['key' => 'product-application-11', 'label' => 'Round Bar', 'x' => 37.63, 'y' => 91.48, 'anchor_x' => 39.95, 'anchor_y' => 75.00],
        ['key' => 'product-application-12', 'label' => 'Hardware Items', 'x' => 53.97, 'y' => 87.69, 'anchor_x' => 52.14, 'anchor_y' => 74.81],
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_column(self::ROWS, 'key');
    }

    /**
     * Thumbnails are cropped from that same composite and committed as
     * static assets, then copied onto the public disk so they behave like
     * any other uploaded image: previewable and replaceable in the admin.
     *
     * Upserts only by the immutable `migration_key`, never by label, so a
     * re-run repairs an edited row instead of adding a thirteenth.
     */
    public function run(): void
    {
        $now = now();
        $disk = Storage::disk('public');

        // The import migration predates the anchor columns, so this seeder is
        // replayed on databases where they do not exist yet. Write only what
        // the schema has.
        $hasAnchors = Schema::hasColumn('product_applications', 'anchor_x')
            && Schema::hasColumn('product_applications', 'anchor_y');

        $updateColumns = ['label', 'image', 'x', 'y', 'sort_order', 'updated_at'];
        if ($hasAnchors) {
            $updateColumns[] = 'anchor_x';
            $updateColumns[] = 'anchor_y';
        }

        $records = [];
        foreach (self::ROWS as $index => $row) {
            $slug = str_replace(' ', '-', strtolower($row['label']));
            $source = public_path('static/images/product-applications/'.$slug.'.png');
            $path = MediaUploadRules::DIRECTORY_PRODUCT_APPLICATION.'/'.$slug.'.png';

            if (is_file($source) && ! $disk->exists($path)) {
                $disk->put($path, (string) file_get_contents($source));
            }

            $record = [
                'migration_key' => $row['key'],
                'label' => $row['label'],
                'image' => $path,
                'video_file' => null,
                'description' => null,
                'x' => $row['x'],
                'y' => $row['y'],
                'is_active' => true,
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($hasAnchors) {
                $record['anchor_x'] = $row['anchor_x'];
                $record['anchor_y'] = $row['anchor_y'];
            }

            $records[] = $record;
        }

        // created_at is insert-only so a re-run keeps the original stamp.
        DB::table('product_applications')->upsert(
            $records,
            ['migration_key'],
            $updateColumns
        );
    }
}
