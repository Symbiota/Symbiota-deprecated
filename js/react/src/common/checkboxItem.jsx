import React from "react";

function CheckboxItem(props) {
	let checked = (props.checked == true? 'checked':'');
  return (
    <span>
      <input type="checkbox" name={ props.name } value={ props.value } onChange={ props.onChange } checked={ checked }/>
      <label className="ml-2 align-middle" htmlFor={ props.name }>{ props.name }</label>
    </span>
  )
}

CheckboxItem.defaultProps = {
  name: '',
  value: 'off',
  onChange: (e) => { console.log(e) }
};

export default CheckboxItem;