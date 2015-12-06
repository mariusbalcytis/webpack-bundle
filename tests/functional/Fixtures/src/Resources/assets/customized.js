function a() {
    require('@a/featureA.css');
    require('@MabaWebpackTestBundle/asset.js');
}
function b() {
    require.ensure([], function() {
        require('featureA.js');
        require('featureB.js');
    });
}
