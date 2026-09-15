# StageArtPlugIn

StageArtの主要な舞台活動機能を、既存のWordPressサイトへ導入できる形で提供するための独立プラグインです。

## 方針

StageArt本体の設計を参照しつつ、WordPress固有の実装とStageArtの業務モデルを分離します。

基本モデルは以下を中心とします。

```text
Person
  ├─ Membership ─→ Organization
  └─ Participant ─→ Production
                         └─ Performance
```

Organization MembershipとProductionへの参加は別物として扱います。

## StageArt本体との関係

StageArt本体では Core と Ticket / Rehearsal / Accounting などのDomain Moduleを境界づける設計方針が採用されています。このプラグインもその思想に従い、WordPress上で独立して利用できる構造を目指します。

## 現在の実装

- WordPress plugin bootstrap
- StageArt管理画面の入口
- `/wp-json/stageart/v1/health` REST endpoint
- Organizationの初期DBスキーマ

## 今後

まずCore相当の最小単位として、Organization / Production / Performance / Membership / Participantを整備し、その後に公開公演ページ、チケット・予約、稽古、受付などのModuleを段階的に追加します。
