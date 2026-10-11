<?php

namespace Tests\Unit;

use App\Services\UsernameService;
use PHPUnit\Framework\TestCase;

class UsernameServiceTest extends TestCase
{
    public function test_attempt_zero_returns_plain_slug(): void
    {
        $candidate = UsernameService::buildCandidate('Omar Ali', 0);
        $this->assertEquals('omar_ali', $candidate);
    }

    public function test_attempt_five_returns_slug_with_four_digit_suffix(): void
    {
        $candidate = UsernameService::buildCandidate('Omar Ali', 5);
        $this->assertMatchesRegularExpression('/^omar_ali_[0-9]{4}$/', $candidate);
    }

    public function test_attempt_ten_returns_slug_with_seven_char_microsecond_suffix(): void
    {
        $candidate = UsernameService::buildCandidate('Omar Ali', 10);
        $this->assertMatchesRegularExpression('/^omar_ali_[0-9]{7}$/', $candidate);
    }
}
