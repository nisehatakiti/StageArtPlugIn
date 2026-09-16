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

## 2. 管理画面の操作

情報解禁日時は、日時入力だけを単独で表示するのではなく、原則としてチェックボックスで有効／無効を制御する。

```text
情報解禁
☐ 情報解禁日を設定する
```

- チェックなし：情報解禁日時を設定しない。コンテンツが公開状態になった時点から公開する。
- チェックあり：日本時間（Asia/Tokyo）で情報解禁日時を入力する。
- チェックを外して保存した場合、保存済みの情報解禁日時も解除し、即時公開扱いに戻す。
- 未設定時に入力欄を無効化しても、既存値が残って再び有効になることがないよう、保存時には解除状態を正として扱う。

表示上は必要に応じて「近日公開」「公演回は後日公開」などのプレースホルダーを各コンテンツ側で表示する。情報解禁設定そのものがチェックなしの場合はプレースホルダーを表示しない。

## 3. 適用対象

情報解禁日時は、公演本体だけでなく、公開される各コンテンツ・情報単位に設定する。

例：

- 公演基本情報
- メンバー一覧
- メンバー個人コンテンツ
- 劇団代表挨拶
- 会場
- 会場地図情報
- 公演回（Performance）
- チケット料金
- 公演クレジットの区分
- その他、今後追加する公開コンテンツ

コンテンツごとに異なる情報解禁日時を設定できる。

## 4. データ保存形式

DBでは情報解禁日時をUTCで保存する。

推奨する保存形式：

```text
release_at DATETIME NULL
```

`NULL` は「情報解禁日時を設定しない（公開状態になった時点から公開）」を意味する。

保存例：

```text
2026-10-01 03:00:00
```

これは日本時間（Asia/Tokyo）の2026年10月1日 12:00:00をUTCに変換した値である。

DBに日本時間の文字列を直接保存せず、UTCを正規値とする。

## 5. 管理画面での入力

管理者にはUTCを意識させず、日本時間で入力させる。

```text
情報解禁
☐ 情報解禁日を設定する
  [2026/10/01] [12:00]
  ※日本時間
```

入力値は`Asia/Tokyo`として解釈し、保存時にUTCへ変換する。

## 6. PHPのタイムゾーン

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

## 7. 公開判定

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
    → 公開状態になった時点から公開

現在時刻 < release_at
    → 未解禁

現在時刻 >= release_at
    → 解禁済み
```

解禁時刻ちょうどを解禁済みとするため、比較は`>=`を使用する。

## 8. 共通サービス

各コンテンツで日時判定を個別実装せず、共通サービスとして提供する。

想定配置：

```text
src/Domain/Release/ReleaseDate.php
```

基本API：

```php
ReleaseDate::isReleased(?string $releaseAt): bool
ReleaseDate::toUtc(?string $jstDateTime): ?string
ReleaseDate::fromUtc(?string $utcDateTime): string
```

共通サービスの責務は、日時の解釈・変換・解禁判定までとし、未解禁時に何を表示するかは各コンテンツ側で決定する。

## 9. 表示方法

ページ全体を非公開にするのではなく、情報単位で表示を切り替える。

例：

```text
公演紹介
→ 公開済み

出演者
→ 近日公開

会場
→ 近日公開

公演回
→ 公演回は後日公開

チケット料金
→ 料金は後日公開
```

解禁日時に到達すると、同一URLのまま通常の情報に切り替わる。

## 10. Performanceへの適用

ProductionとPerformanceは別の概念として扱う。

Performanceは**1公演回ごとに独立した情報単位**として情報解禁日時を持つ。

```text
Production
│
├─ Performance A
│   └─ release_at
├─ Performance B
│   └─ release_at
└─ Performance C
    └─ release_at
```

したがって、A公演だけ先に解禁し、B公演・C公演を後から解禁できる。

公演回ブロック全体を一括で解禁する`performance_release`は使用しない。

## 11. 原則

StageArtPlugInにおける情報解禁日時は、以下を正式仕様とする。

> 情報解禁日時は、公開コンテンツ単位で設定する。管理画面では「情報解禁日を設定する」チェックボックスで有効／無効を制御し、チェックなしは公開状態になった時点から公開する。チェックありの場合は日本時間（Asia/Tokyo）で入力し、DBにはUTCの`DATETIME`として保存する。公開時の判定はアクセス時にPHPで行い、現在時刻が解禁日時以上となった時点で解禁済みとする。情報解禁のためのcron等による状態変更は行わない。Performanceは1公演回ごとに独立して解禁日時を設定する。
