<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('portfolios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('name', 120);
            $table->char('base_currency', 3)->default('USD');
            $table->timestamps();
        });

        Schema::create('portfolio_memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 12);
            $table->timestamps();
            $table->unique(['portfolio_id', 'user_id']);
            $table->index(['user_id', 'portfolio_id']);
        });

        Schema::create('assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16);
            $table->string('name', 120);
            $table->string('symbol', 32)->nullable();
            $table->decimal('quantity', 24, 8)->default(0);
            $table->decimal('unit_price', 24, 8);
            $table->timestamp('valued_at');
            $table->timestamps();
            $table->unique(['portfolio_id', 'type', 'name']);
            $table->unique(['id', 'portfolio_id']);
            $table->index(['portfolio_id', 'type']);
        });

        Schema::create('asset_valuations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->decimal('unit_price', 24, 8);
            $table->timestamp('valued_at');
            $table->timestamps();
            $table->foreign(['asset_id', 'portfolio_id'], 'valuation_asset_tenant_fk')
                ->references(['id', 'portfolio_id'])->on('assets')->restrictOnDelete();
            $table->index(['asset_id', 'valued_at', 'id']);
        });

        Schema::create('portfolio_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id');
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('type', 8);
            $table->decimal('quantity', 24, 8);
            $table->decimal('unit_price', 24, 8);
            $table->uuid('idempotency_key');
            $table->timestamp('executed_at');
            $table->timestamps();
            $table->foreign(['asset_id', 'portfolio_id'], 'transaction_asset_tenant_fk')
                ->references(['id', 'portfolio_id'])->on('assets')->restrictOnDelete();
            $table->unique(['portfolio_id', 'idempotency_key'], 'transaction_idempotency_unique');
            $table->index(['portfolio_id', 'executed_at', 'id']);
            $table->index(['asset_id', 'executed_at']);
        });

        // MySQL 8.4 enforces CHECK constraints (8.0.16+). Avoid app-only financial invariants.
        if (DB::getDriverName() === 'mysql') {
            foreach ([
                "ALTER TABLE portfolios ADD CONSTRAINT currency_usd_only CHECK (base_currency = 'USD')",
                "ALTER TABLE portfolio_memberships ADD CONSTRAINT role_allowed CHECK (role IN ('owner','editor','viewer'))",
                "ALTER TABLE assets ADD CONSTRAINT asset_class_allowed CHECK (type IN ('stock','crypto','real_estate','cash'))",
                'ALTER TABLE assets ADD CONSTRAINT asset_values_nonnegative CHECK (quantity >= 0 AND unit_price >= 0)',
                "ALTER TABLE assets ADD CONSTRAINT usd_cash_parity CHECK (type <> 'cash' OR unit_price = 1)",
                'ALTER TABLE asset_valuations ADD CONSTRAINT valuation_nonnegative CHECK (unit_price >= 0)',
                "ALTER TABLE portfolio_transactions ADD CONSTRAINT transaction_valid CHECK (type IN ('buy','sell') AND quantity > 0 AND unit_price >= 0)",
            ] as $statement) {
                DB::statement($statement);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_transactions');
        Schema::dropIfExists('asset_valuations');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('portfolio_memberships');
        Schema::dropIfExists('portfolios');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
