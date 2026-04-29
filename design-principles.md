# Design Principles (ZAC SVC Site)

- Part は製品を知らない（純部品マスタ）
- Product が Category / Sub Category を持つ
- 分類は Taxonomy、属性は ACF
- 図面番号（No.）は「部品」ではなく「製品での使われ方」
- 共通パーツは Product–Part 中間構造で管理する
- UI 付加機能（Split Pane / Zoom / 図面連動）は
  DB 完成後でも追加できる設計にする
- 検索正規化は Model Alias に集約する

