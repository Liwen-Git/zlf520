webpackJsonp([3],{"3q1t":function(t,e,n){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var s=n("j78L"),a=(n("DHfu"),n("Au9i")),o={data:function(){return{phone:"151 **** 2530",sendState:1,code:"",num:60,intervalId:"",temp_token:""}},created:function(){this.phone=this.tokus.phone},methods:{goBack:function(){this.$router.go(-1)},submit:function(){var t=this,e=this.code;Object(s.b)({verify_code:e}).then(function(e){0==e.code?(t.temp_token=e.data.temp_token,t.$router.push({path:"/setPassword/"+t.temp_token})):Object(a.Toast)(e.message)})},sendYzm:function(t){var e=this;if(2==t)return!1;Object(s.c)({mobile:this.phone}).then(function(t){0===t.code?Object(a.Toast)("发送成功"):Object(a.Toast)(t.message)}),this.sendState=2,this.intervalId=setInterval(function(){e.num>0?e.num--:(clearInterval(e.intervalId),e.sendState=3,e.num=60)},1e3)}}},i={render:function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("div",{attrs:{id:"changePassword"}},[n("mt-header",{staticStyle:{height:".88rem","background-color":"#4a57ff"},attrs:{title:"修改密码"}},[n("mt-button",{attrs:{slot:"left",icon:"back"},on:{click:t.goBack},slot:"left"})],1),t._v(" "),n("div",{staticClass:"content"},[n("div",{staticClass:"item_input"},[n("div",{staticClass:"laber"},[t._v("手机号码")]),t._v(" "),n("input",{directives:[{name:"model",rawName:"v-model",value:t.phone,expression:"phone"}],attrs:{type:"text",disabled:""},domProps:{value:t.phone},on:{input:function(e){e.target.composing||(t.phone=e.target.value)}}}),t._v(" "),n("div",{staticClass:"sendYzm",on:{click:t.sendYzm}},[1==t.sendState?n("span",[t._v("发送验证码")]):t._e(),2==t.sendState?n("span",[t._v(t._s(t.num)+"s")]):t._e(),3==t.sendState?n("span",[t._v("重新获取")]):t._e()])]),t._v(" "),n("div",{staticClass:"item_input"},[n("div",{staticClass:"laber"},[t._v("验证码")]),t._v(" "),n("input",{directives:[{name:"model",rawName:"v-model",value:t.code,expression:"code"}],attrs:{type:"text",placeholder:"请输入验证码"},domProps:{value:t.code},on:{input:function(e){e.target.composing||(t.code=e.target.value)}}})]),t._v(" "),n("mt-button",{staticClass:"submit",attrs:{type:"primary",disabled:!t.code},on:{click:t.submit}},[t._v("确定")])],1)],1)},staticRenderFns:[]};var c=n("VU/8")(o,i,!1,function(t){n("E8ld")},"data-v-1ef29784",null);e.default=c.exports},E8ld:function(t,e){}});
//# sourceMappingURL=3.5d1b8194ba4f5d8de842.js.map