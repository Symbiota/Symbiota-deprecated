import React from "react";

function CheckboxItem(props) {
  return (
    <span>
      <input type="checkbox" name={ props.name } value={ props.value } onChange={ props.onChange } />
      <label className="text-capitalize ml-2 align-middle" htmlFor={ props.name }>{ props.name }</label>
    </span>
  )
}

CheckboxItem.defaultProps = {
  name: '',
  value: 'off',
  onChange: (e) => { console.log(e) }
};

export default CheckboxItem;