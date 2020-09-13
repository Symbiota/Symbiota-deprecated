import React from "react";

class HelpButton extends React.Component {
  constructor(props) {
    super(props);
    this.getHelpButtonId = this.getHelpButtonId.bind(this);
  }

  getHelpButtonId() {
    return this.props.title.toLowerCase().replace(/[^a-z]/g, '') + "-help";
  }

  componentDidMount() {
    const helpButtonId = this.getHelpButtonId();
    $(`#${helpButtonId}`).popover({
      title: this.props.title,
      html: true,
      trigger: "focus",
      placement: "bottom",
      content: this.props.html,
    });
  }

  render() {
    return (
      <button id={ this.getHelpButtonId() } className="help-button">
        <img
          style={{ width: "1.25em" }}
          alt="help"
          src={ `${this.props.clientRoot}/images/garden/help.png` }
        />
      </button>
    );
  }
}

export default HelpButton;