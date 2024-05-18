<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Medicine;
use Illuminate\Support\Facades\DB;

class MedicineSeeder extends Seeder
{
    public function run()
    {
        // Delete existing records to start fresh
        Medicine::query()->delete();

        // Reset auto-increment to 1
        DB::statement("ALTER TABLE medicines AUTO_INCREMENT = 1;");

        $medicinesData = [
            [
                'name' => 'Aspirin',
                'name_ar' => 'أسبرين',
                'category' => 'Pain Reliever',
                'category_ar' => 'مسكن للألم',
                'description' => 'Used to reduce fever, pain, and inflammation. Low doses prevent blood clots.',
                'description_ar' => 'يستخدم لتقليل الحمى والألم والالتهابات. الجرعات المنخفضة تمنع تكون الجلطات الدموية.',
                'price' => 5.99,
                'stock' => 100,
                'image_url' => 'aspirin.png'
            ],
            [
                'name' => 'Ibuprofen',
                'name_ar' => 'إيبوبروفين',
                'category' => 'Pain Reliever',
                'category_ar' => 'مسكن للألم',
                'description' => 'Effective in reducing fever and relieving pain from various conditions.',
                'description_ar' => 'فعال في تقليل الحمى وتخفيف الألم من الحالات المختلفة.',
                'price' => 7.99,
                'stock' => 80,
                'image_url' => 'ibuprofen.png'
            ],
            [
                'name' => 'Paracetamol',
                'name_ar' => 'باراسيتامول',
                'category' => 'Pain Reliever',
                'category_ar' => 'مسكن للألم',
                'description' => 'Relieves pain and reduces fever, suitable for those who cannot take NSAIDs.',
                'description_ar' => 'يخفف الألم ويقلل الحرارة، مناسب لمن لا يستطيعون تناول الأدوية المضادة للالتهابات غير الستيرويدية.',
                'price' => 4.50,
                'stock' => 120,
                'image_url' => 'paracetamol.png'
            ],
            [
                'name' => 'Amoxicillin',
                'name_ar' => 'أموكسيسيلين',
                'category' => 'Antibiotic',
                'category_ar' => 'مضاد حيوي',
                'description' => 'Used to treat a variety of bacterial infections.',
                'description_ar' => 'يستخدم لعلاج مجموعة متنوعة من الالتهابات البكتيرية.',
                'price' => 12.00,
                'stock' => 95,
                'image_url' => 'amoxicillin.png'
            ],
            [
                'name' => 'Lisinopril',
                'name_ar' => 'ليزينوبريل',
                'category' => 'Blood Pressure Medication',
                'category_ar' => 'دواء ضغط الدم',
                'description' => 'Treats high blood pressure and heart failure, improves survival after heart attack.',
                'description_ar' => 'يعالج ارتفاع ضغط الدم وفشل القلب، يحسن البقاء على قيد الحياة بعد النوبة القلبية.',
                'price' => 15.00,
                'stock' => 60,
                'image_url' => 'lisinopril.png'
            ],
            [
                'name' => 'Metformin',
                'name_ar' => 'ميتفورمين',
                'category' => 'Diabetes Medication',
                'category_ar' => 'دواء السكري',
                'description' => 'Improves blood sugar control in people with type 2 diabetes.',
                'description_ar' => 'يحسن التحكم في نسبة السكر في الدم لدى الأشخاص المصابين بالسكري من النوع الثاني.',
                'price' => 10.00,
                'stock' => 100,
                'image_url' => 'metformin.png'
            ],
            [
                'name' => 'Omeprazole',
                'name_ar' => 'أوميبرازول',
                'category' => 'Gastrointestinal Medication',
                'category_ar' => 'دواء الجهاز الهضمي',
                'description' => 'Treats acid reflux and ulcers by reducing stomach acid.',
                'description_ar' => 'يعالج الارتجاع المعدي المريئي والقرحة عن طريق تقليل حمض المعدة.',
                'price' => 18.00,
                'stock' => 50,
                'image_url' => 'omeprazole.png'
            ],
            [
                'name' => 'Cetirizine',
                'name_ar' => 'سيتريزين',
                'category' => 'Allergy Medication',
                'category_ar' => 'دواء الحساسية',
                'description' => 'Antihistamine that relieves allergy symptoms.',
                'description_ar' => 'مضاد للهستامين يخفف أعراض الحساسية.',
                'price' => 8.00,
                'stock' => 90,
                'image_url' => 'cetirizine.png'
            ],
            [
                'name' => 'Simvastatin',
                'name_ar' => 'سيمفاستاتين',
                'category' => 'Cholesterol Medication',
                'category_ar' => 'دواء الكوليسترول',
                'description' => 'Lowers cholesterol and triglycerides in the blood.',
                'description_ar' => 'يخفض الكوليسترول والدهون الثلاثية في الدم.',
                'price' => 20.00,
                'stock' => 70,
                'image_url' => 'simvastatin.png'
            ],
            [
                'name' => 'Amlodipine',
                'name_ar' => 'أملوديبين',
                'category' => 'Blood Pressure Medication',
                'category_ar' => 'دواء ضغط الدم',
                'description' => 'Used to treat high blood pressure and angina.',
                'description_ar' => 'يستخدم لعلاج ارتفاع ضغط الدم والذبحة الصدرية.',
                'price' => 17.00,
                'stock' => 85,
                'image_url' => 'amlodipine.png'
            ]
        ];

        // Insert the records into the database
        foreach ($medicinesData as $data) {
            Medicine::create($data);
        }
    }
}
