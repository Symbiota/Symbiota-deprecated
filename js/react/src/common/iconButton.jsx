import React from "react";

class IconButton extends React.Component {
  render()
  {
    const selectedStyle = {
      background: "#DFEFD3",
      color: "#3B631D"
    };

    const unselectedStyle = {
      color: "#9FD07A"
    };

    return (
      <span
        className="fake-button align-middle justify-content-center"
        style={ Object.assign({}, this.props.isSelected ? selectedStyle : unselectedStyle, this.props.style) }
        onClick={ this.props.onClick }
      >
      <img
        alt="viewType"
        width="1em"
        height="1em"
        className="mx-1"
        style={{ display: (this.props.icon === "" ? "none" : "inline-block"), width: "1em", height: "1em" }}
        src={ this.props.icon }
        onClick={ this.props.onClickImg ? this.props.onClickImg : this.props.onClick }
      />
      <span dangerouslySetInnerHTML={{__html: this.props.title}} ></span>
    </span>
    );
  }
}

IconButton.defaultProps = {
  title: "",
  icon: "",
  style: {},
  onClick: null,
  onClickImg: null
};

export default IconButton;