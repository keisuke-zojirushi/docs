\# ZATI 開発ルール



\## プロジェクト概要



ZATI は、Zojirushi America Technical Support と Service Center（SVC）向けの

WordPressベースの社内システムです。



主な目的：



\- Model Search

\- Parts Search

\- Parts Order

\- Warranty Claim

\- Backorder管理

\- Technical Information

\- Excel / PDF出力

\- 将来的なSupport Ticket、Repair Log、Inventory管理



ZATIは既存のZOISやKintoneに慣れているユーザーが、

できるだけ少ないトレーニングで使えることを重視する。





\## 使用技術



\- WordPress

\- PHP

\- JavaScript

\- CSS

\- MySQL

\- Advanced Custom Fields（ACF）

\- WP All Import

\- Dompdf

\- PhpSpreadsheet

\- Custom Plugin: zati-tools





\## 重要な開発ルール



ZATIを修正するときは以下を守る。



1\. 一度に大きな変更をしない。

2\. 動いている機能をできるだけ壊さない。

3\. 必要のないコードは変更しない。

4\. デバッグでは1つずつ修正する。

5\. 大きなファイル全体を書き換えない。

6\. 既存のZATIの構造・UI・URLをできるだけ維持する。

7\. 新しいFrameworkを安易に追加しない。

8\. Admin、ZAC TS、SVCの権限の違いを必ず確認する。





\## AIがコード修正を提案するとき



必ず以下を明確にする。



1\. 修正するファイル名

2\. どの既存コードを探すか

3\. ADD / DELETE / REPLACE のどれか

4\. 正確な修正コード

5\. 修正後に何を確認するか



「functions.phpの適当な場所に追加してください」

のような曖昧な指示は禁止。



コードの場所が不明な場合は、先に周辺コードを確認する。





\## 主なユーザーロール



\### Administrator



`administrator`



システム管理者。



\### ZAC Technical Support



`zac\_ts`



Technical Support Staff。



Administratorとzac\_tsは、通常Staff側のユーザーとして扱う。



\### US / Canada SVC



`us\_canada\_svc`



\### Canada Parts Sales



`canada\_parts\_sales`



\### Mexico SVC



`mexico\_svc`





\## 権限について



画面上でボタンを隠すだけではなく、

サーバー側でもRole / Capabilityを確認する。



特に以下の内部機能はSVCに公開しない。



\- TS Processing

\- Internal Shipping Information

\- Open Backorders管理

\- Backorder Release

\- Staff専用情報





\## Parts Order



基本フロー：



SVC

→ Parts Order

→ Review

→ Submit

→ Submitted

→ TS Processing

→ TS Completed





新規Parts Orderは、



`/parts-order/?new=1`



を使用する。



これにより以前選択したPartのsessionStorageをクリアする。





\## Parts Order Review



Review画面はPost/Redirect/Get（PRG）方式を使用している。



ブラウザーのBack操作で、



\- Confirm Form Resubmission

\- ERR\_CACHE\_MISS



が発生しないようにしている。



この仕組みをPOST-only方式に戻さない。





\## TS Processing



TS Processingを操作できるRole：



\- administrator

\- zac\_ts



主な項目：



\- Ordered

\- Confirmed

\- Backorder

\- Unavailable

\- TS Note



重要なValidation：



Confirmed + Backorder <= Ordered



エラーがある場合：



\- DBへ保存しない

\- Order Statusを変更しない

\- TS Completed Dateを設定しない

\- 入力した数字を画面に残す

\- エラーのあるすべてのPartを表示する





\## Backorder



主なページ：



`/open-backorders/`



`/backorder-history/`



基本的にAdministrator / ZAC TS専用。





\## Partデータ



主なField：



\- model

\- no

\- partnumber

\- jpnumber

\- description

\- size

\- distributor\_price

\- retail\_price

\- remarks



将来：



\- inventory\_04

\- inventory\_51





\## Model Parts Import



Unique IDは原則として、



`{model}-{no}-{partnumber}`



を使用する。



partnumberだけをUnique IDにしない。





\## 価格ルール



US / Canada SVC：



Distributor Price × 82%



Mexico SVC：



Distributor Price × 100%



Canada Parts Sales：



Distributor Price × 82% × 85%



価格計算は明確な指示なしに変更しない。





\## WP All Import



大量データImportに使用する。



ZATIはACFを使用している。



既存データを更新するときは、

可能な限り



`Choose which data to update`



を使用し、



`Update all data`



で不要なFieldまで上書きしない。





\## SVC Price と Retail Price



SVC Price：



Service Centerが購入する価格。



Retail Price：



修理時などにCustomerへ提示する価格。



この2つを同じものとして扱わない。





\## Plugin



ZATIの重要な処理の一部は、



`zati-tools`



Plugin側にある。



Themeだけを見てZATI全体の処理を判断しない。





\## セキュリティ



必ず以下を考慮する。



\- Nonce

\- Sanitization

\- Escaping

\- Role / Capability

\- Direct URL Access

\- File Upload

\- Service Center間の情報漏洩

\- Duplicate Submission





\## Git



大きな修正前には、



`git status`



を確認する。



正常に動作する区切りごとにCommitする。



Password、API Key、Private KeyなどはGitへ保存しない。





\## 開発方針



理論的にきれいな設計よりも、

現在正常に動いているZATIの安定性を優先する。



大規模なRefactoringは、

明確な必要性がある場合だけ行う。





\## AIとのやり取り



AIは以下の形式で対応する。



\- 簡潔に説明する

\- 一度に1つの修正を行う

\- どこにコードを入れるか明確にする

\- テスト結果を確認してから次へ進む

\- 不明なコード構造を推測しない

\- 既存コードを確認してから変更する

