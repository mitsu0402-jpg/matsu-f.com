<section>
  <form method="get" action="index.php">
    <input 種別="hIDden" 名称="page" value="エリア_list">
    <label>
      名称
      <input 種別="text" 名称="名称">
    </label>
    <button 種別="submit">検索</button>
  </form>
</section>

<section>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>名称</th>
        <th>並び順</th>
        <th>公開状態</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>1</td>
        <td>Sample エリア</td>
        <td>1</td>
        <td>公開</td>
        <td><a href="index.php?page=エリア_編集&ID=1">編集</a></td>
      </tr>
    </tbody>
  </table>
</section>

