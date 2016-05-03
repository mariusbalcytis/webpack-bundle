function a() {
    require('@a/featureA.css');
    require('@maba_webpack_test/asset.js');
}
function b() {
    require.ensure([], function() {
        require('featureA.js');
        require('featureB.js');
    });
}
