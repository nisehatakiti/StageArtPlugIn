# 情報解禁日時 共通仕様

StageArtPlugInでは、情報解禁日時をサイト上で公開される各コンテンツ・情報単位に設定できる共通仕様として扱う。

## 1. 基本方針

情報解禁日時は、WordPressページ・コンテンツ自体の公開状態とは独立して管理する。

ページ自体は通常の公開状態にしておき、アクセス時にPHPが情報解禁日時を判定して表示内容を切り替える。

情報解禁のために、WordPressの予約公開、投稿ステータス変更、cron、WP-Cronによる自動更新は使用しない。

```text
WordPress公開状態
    ↓
公開済み
    ↓
アクセス時にPHPで解禁判定
    ├─ 未解禁 → 未解禁用の表示
    └─ 解禁済み → 通常表示
```

## 2. 適用対象

情報解禁日時は、公演本体だけでなく、公開される各コンテンツ・情報単位に設定する。

例：

- 公演基本情報
- メンバー一覧
- メンバー個人コンテンツ
- 会場
- 公演回（Performance）
- チケット料金
- その他、今後追加する公開コンテンツ

コンテンツごとに異なる情報解禁日時を設定できる。

## 3. データ保存形式

DBでは情報解禁日時をUTCで保存する。

推奨する保存形式：

```text
release_at DATETIME NULL
```

`NULL` は「情報解禁日時を設定しない」を意味し、常時解禁済みとして扱う。

保存例：

```text
2026-10-01 03:00:00
```

これは日本時間（Asia/Tokyo）の2026年10月1日 12:00:00をUTCに変換した値である。

DBに日本時間の文字列を直接保存せず、UTCを正規値とする。

## 4. 管理画面での入力

管理者にはUTCを意識させず、日本時間で入力させる。

```text
情報解禁日時
[2026/10/01] [12:00]
※日本時間
```

入力値は`Asia/Tokyo`として解釈し、保存時にUTCへ変換する。

## 5. PHPのタイムゾーン

PHPのデフォルトタイムゾーンには依存しない。

日本時間を扱う際は、必ず`Asia/Tokyo`を明示する。

```php
$timezone = new DateTimeZone('Asia/Tokyo');

$releaseAt = new DateTimeImmutable(
    '2026-10-01 12:00:00',
    $timezone
);
```

UTCへ変換する場合：

```php
$releaseAtUtc = $releaseAt->setTimezone(
    new DateTimeZone('UTC')
);
```

## 6. 公開判定

公開ページでは現在時刻をUTCで取得し、DBの`release_at`と比較する。

```php
$now = new DateTimeImmutable(
    'now',
    new DateTimeZone('UTC')
);

$releaseAt = new DateTimeImmutable(
    $row['release_at'],
    new DateTimeZone('UTC')
);

$isReleased = $now >= $releaseAt;
```

判定ルール：

```text
release_at = NULL
    → 常に解禁済み

現在時刻 < release_at
    → 未解禁

現在時刻 >= release_at
    → 解禁済み
```

解禁時刻ちょうどを解禁済みとするため、比較は`>=`を使用する。

## 7. 共通サービス

各コンテンツで日時判定を個別実装せず、共通サービスとして提供する。

想定配置：

```text
src/Domain/Release/ReleaseDate.php
```

基本APIの例：

```php
ReleaseDate::isReleased(?string $releaseAt): bool
```

必要に応じて、日本時間入力とUTC保存値の変換も共通化する。

```php
ReleaseDate::toUtc(string $jstDateTime): string
ReleaseDate::fromJst(string $jstDateTime): string
```

共通サービスの責務は、日時の解釈・変換・解禁判定までとし、未解禁時に何を表示するかは各コンテンツ側で決定する。

## 8. 表示方法

ページ全体を非公開にするのではなく、情報単位で表示を切り替える。

例：

```text
公演紹介
→ 公開済み

出演者
→ 近日公開

会場
→ 近日公開

公演日時
→ 詳細は後日公開

チケット料金
→ 料金は後日発表
```

解禁日時に到達すると、同一URLのまま通常の情報に切り替わる。

## 9. Performanceへの適用

StageArt本体のDomain Modelに合わせ、ProductionとPerformanceは別の概念として扱う。

Performanceも独立した情報単位として情報解禁日時を持てるようにする。

```text
Production
│
├─ 基本情報
│   └─ release_at
│
├─ Performance A
│   └─ release_at
│
├─ Performance B
│   └─ release_at
│
└─ Performance C
    └─ release_at
```

これにより、各公演回を異なる日時に解禁できる。

## 10. 原則

StageArtPlugInにおける情報解禁日時は、以下を正式仕様とする。

> 情報解禁日時は、公開コンテンツ単位で設定する。ページ自体の公開状態とは独立して管理し、ページは通常の公開状態のまま保持する。情報解禁日時は管理画面では日本時間（Asia/Tokyo）で入力し、DBにはUTCの`DATETIME`として保存する。公開時の判定はアクセス時にPHPで行い、現在時刻が解禁日時以上となった時点で解禁済みとする。情報解禁のためのcron等による状態変更は行わない。解禁日時がNULLの場合は常時解禁済みとする。
