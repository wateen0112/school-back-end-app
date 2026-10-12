<?php

namespace Tests\Feature;

use App\Support\ApiMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The API localises its `message` / `errors` text from the request locale, so
 * the dashboard and mobile app receive Arabic when they ask for it.
 */
class ApiLocalizationTest extends TestCase
{
    use RefreshDatabase;

    /** Locale resolution from the request, header or query. */
    #[Test]
    #[DataProvider('localeHeaderProvider')]
    public function unauthenticated_response_honours_requested_language(string $header, string $value, string $expected)
    {
        $response = $this->withHeader($header, $value)->getJson('/api/students');

        $response->assertStatus(401)->assertJsonPath('success', false);

        $expectedMessage = $expected === 'ar' ? 'يجب تسجيل الدخول أولاً.' : 'Unauthenticated.';

        $this->assertSame($expectedMessage, $response->json('message'));
    }

    public static function localeHeaderProvider(): array
    {
        return [
            'lang ar' => ['lang', 'ar', 'ar'],
            'lang en' => ['lang', 'en', 'en'],
            'lang uppercase' => ['lang', 'AR', 'ar'],
            'lang with region' => ['lang', 'ar-EG', 'ar'],
            'X-Lang' => ['X-Lang', 'ar-SA', 'ar'],
            'X-Locale' => ['X-Locale', 'ar', 'ar'],
            'accept-language ar' => ['Accept-Language', 'ar-EG,ar;q=0.9,en;q=0.8', 'ar'],
            'accept-language en' => ['Accept-Language', 'en-US,en;q=0.9', 'en'],
        ];
    }

    #[Test]
    public function query_parameter_selects_the_language()
    {
        $this->getJson('/api/students?lang=ar')
            ->assertStatus(401)
            ->assertJsonPath('message', 'يجب تسجيل الدخول أولاً.');

        $this->getJson('/api/students?lang=en')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    #[Test]
    public function unknown_locale_falls_back_without_breaking_the_response()
    {
        $this->withHeader('lang', 'xx-YY')
            ->getJson('/api/students')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    #[Test]
    public function localized_response_advertises_its_language()
    {
        $response = $this->withHeader('lang', 'ar')->getJson('/api/students');

        $this->assertSame('ar', $response->headers->get('Content-Language'));
    }

    // ----- translator unit coverage -----

    #[Test]
    public function english_is_the_source_language_and_passes_through()
    {
        app()->setLocale('en');
        ApiMessage::flush();

        $this->assertSame('Student not found', ApiMessage::translate('Student not found'));
    }

    #[Test]
    public function known_sentences_are_translated()
    {
        app()->setLocale('ar');
        ApiMessage::flush();

        $this->assertSame('الطالب غير موجود', ApiMessage::translate('Student not found'));
        $this->assertSame('تم تنفيذ الطلب بنجاح', ApiMessage::translate('Request completed successfully'));
    }

    #[Test]
    public function unknown_sentences_are_never_mangled()
    {
        app()->setLocale('ar');
        ApiMessage::flush();

        $original = 'The weasel of the class of 2019 wobbled.';

        $this->assertSame($original, ApiMessage::translate($original));
    }

    #[Test]
    public function partially_translated_output_is_rejected()
    {
        app()->setLocale('ar');
        ApiMessage::flush();

        // A naive word-by-word swap would emit "تم البريد taken. بالفعل".
        $this->assertStringNotContainsString('taken', ApiMessage::translate('The email has already been taken.'));
    }

    #[Test]
    public function already_arabic_text_is_left_alone()
    {
        app()->setLocale('ar');
        ApiMessage::flush();

        $this->assertSame('حقل البريد الإلكتروني مطلوب.', ApiMessage::translate('حقل البريد الإلكتروني مطلوب.'));
    }

    #[Test]
    public function validation_error_maps_are_translated_per_field()
    {
        app()->setLocale('ar');
        ApiMessage::flush();

        $errors = ApiMessage::translateErrors([
            'name' => ['The name field is required.'],
            'email' => ['The email must be a valid email address.'],
        ]);

        $this->assertSame('حقل الاسم مطلوب.', $errors['name'][0]);
        $this->assertStringNotContainsString('The ', $errors['email'][0]);
    }

    #[Test]
    public function row_data_is_never_translated()
    {
        app()->setLocale('ar');
        ApiMessage::flush();

        // `complaints.message` is user authored content, so row payloads must be
        // passed through even when they happen to look like a sentence.
        $body = [
            'success' => true,
            'message' => 'Records retrieved successfully',
            'data' => [
                ['id' => 1, 'message' => 'Student not found'],
            ],
        ];

        $translated = ApiMessage::translateBody($body);

        $this->assertSame('تم استرجاع السجلات بنجاح', $translated['message']);
        $this->assertSame('Student not found', $translated['data'][0]['message']);
    }

    #[Test]
    public function technical_error_detail_is_never_translated()
    {
        app()->setLocale('ar');
        ApiMessage::flush();

        $detail = 'SQLSTATE[HY000] [1045] Access denied for user';

        $translated = ApiMessage::translatePayload([
            'success' => false,
            'message' => 'Server error.',
            'error' => $detail,
        ]);

        $this->assertSame($detail, $translated['error']);
        $this->assertSame('حدث خطأ في الخادم.', $translated['message']);
    }

    #[Test]
    public function arabic_validation_messages_use_arabic_attribute_names()
    {
        app()->setLocale('ar');

        $messages = trans('validation.required', ['attribute' => trans('validation.attributes.name')]);

        $this->assertSame('حقل الاسم مطلوب.', $messages);
    }
}