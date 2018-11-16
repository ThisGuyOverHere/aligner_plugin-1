import React, {Component} from 'react';
import PropTypes from "prop-types";
import { Icon } from 'semantic-ui-react'

class SearchControlsComponent extends Component {
    static propTypes = {
        featuredSearchResult: PropTypes.number,
        occurrencesList: PropTypes.array,
        setFeatured: PropTypes.func,
        close: PropTypes.func
    };

    constructor(props) {
        super(props);
        this.state = {
            featuredSearchResult: this.props.featuredSearchResult,
            occurrencesList: [],
        };
    }


    componentDidMount() {
    }

    componentWillUnmount() {

    }

    static getDerivedStateFromProps = (props, state) => {
        state.featuredSearchResult = props.featuredSearchResult;
        state.occurrencesList = props.occurrencesList;
        return state;
    };

    render() {
        return (
            <div id="controls">
                <Icon className={"increment"} name='chevron up' onClick={() => this.props.setFeatured(this.props.featuredSearchResult -1)}/>
                <Icon className={"decrement"} name='chevron down' onClick={() => this.props.setFeatured(this.props.featuredSearchResult + 1)}/>
                <Icon className={"close"} name='x' onClick={ this.props.close}/>
            </div>
        );
    }


}

export default SearchControlsComponent;
